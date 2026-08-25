<?php

namespace App\Services\Implementations;

use App\DTOs\OrderItem\CreateOrderItemDTO;
use App\DTOs\OrderItem\UpdateOrderItemDTO;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Services\Interfaces\OrderItemServiceInterface;
use App\Services\Interfaces\OrderServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderItemService implements OrderItemServiceInterface
{
    public function __construct(
        private readonly OrderItemRepositoryInterface $items,
        private readonly ProductRepositoryInterface $products,
        private readonly OrderServiceInterface $orders,
    ) {}

    /**
     * Get a paginated list of the line items of an order.
     *
     * @return LengthAwarePaginator<int, OrderItem>
     */
    public function listForOrder(Order $order, int $perPage = 15): LengthAwarePaginator
    {
        return $order->items()->with('product')->latest()->paginate($perPage);
    }

    /**
     * Add a line item to an order.
     *
     * The unit price is copied from the product, and the parent order total
     * is recomputed inside the same transaction.
     */
    public function create(Order $order, CreateOrderItemDTO $dto): OrderItem
    {
        $this->orders->ensureEditable($order);

        return DB::transaction(function () use ($order, $dto): OrderItem {
            $product = $this->products->findByPublicId($dto->productPublicId);

            if ($product === null) {
                throw new ResourceNotFoundException("Produit {$dto->productPublicId} introuvable.");
            }

            $unitPrice = (string) $product->unit_price;

            $item = $this->items->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $dto->quantity,
                'unit_price' => $unitPrice,
                'subtotal' => bcmul($unitPrice, (string) $dto->quantity, 2),
            ]);

            $this->orders->recalculateTotal($order);

            return $item->load('product');
        });
    }

    /**
     * Update a line item.
     */
    public function update(OrderItem $item, UpdateOrderItemDTO $dto): OrderItem
    {
        $this->orders->ensureEditable($item->order);

        if ($dto->isEmpty()) {
            return $item->load('product');
        }

        return DB::transaction(function () use ($item, $dto): OrderItem {
            $attributes = [];
            $unitPrice = (string) $item->unit_price;

            if ($dto->productPublicId !== null) {
                $product = $this->products->findByPublicId($dto->productPublicId);

                if ($product === null) {
                    throw new ResourceNotFoundException("Produit {$dto->productPublicId} introuvable.");
                }

                $attributes['product_id'] = $product->id;
                $unitPrice = (string) $product->unit_price;
                $attributes['unit_price'] = $unitPrice;
            }

            $quantity = $dto->quantity ?? $item->quantity;

            if ($dto->quantity !== null) {
                $attributes['quantity'] = $dto->quantity;
            }

            $attributes['subtotal'] = bcmul($unitPrice, (string) $quantity, 2);

            $updated = $this->items->update($item->id, $attributes) ?? $item;

            $this->orders->recalculateTotal($item->order);

            return $updated->load('product');
        });
    }

    /**
     * Remove a line item from its order.
     */
    public function delete(OrderItem $item): bool
    {
        $this->orders->ensureEditable($item->order);

        return DB::transaction(function () use ($item): bool {
            $order = $item->order;

            $deleted = $this->items->delete($item->id);

            $this->orders->recalculateTotal($order);

            return $deleted;
        });
    }
}
