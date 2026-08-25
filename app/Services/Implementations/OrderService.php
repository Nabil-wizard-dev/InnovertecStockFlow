<?php

namespace App\Services\Implementations;

use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\UpdateOrderDTO;
use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Order;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Services\Interfaces\OrderServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderItemRepositoryInterface $items,
        private readonly CustomerRepositoryInterface $customers,
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * Get a paginated list of orders.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function list(int $perPage = 15, ?OrderStatus $status = null): LengthAwarePaginator
    {
        return $this->orders->paginate($perPage, $status);
    }

    /**
     * Create a new order along with its line items.
     *
     * The unit price of each line is copied from the product at the time of
     * the order, so a later price change never rewrites past orders. The
     * order total is derived from the lines, never sent by the client.
     *
     * Note: stock is NOT decremented here. Orders are created as drafts and
     * `available_quantity` stays under manual control.
     */
    public function create(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto): Order {
            $customer = $this->customers->findByPublicId($dto->customerPublicId);

            if ($customer === null) {
                throw new ResourceNotFoundException("Client {$dto->customerPublicId} introuvable.");
            }

            $order = $this->orders->create([
                'customer_id' => $customer->id,
                'order_reference' => $dto->orderReference ?? $this->generateReference(),
                'total_amount' => 0,
                'status' => $dto->status,
            ]);

            $total = '0';

            foreach ($dto->items as $line) {
                $product = $this->products->findByPublicId($line->productPublicId);

                if ($product === null) {
                    throw new ResourceNotFoundException("Produit {$line->productPublicId} introuvable.");
                }

                $unitPrice = (string) $product->unit_price;
                $subtotal = bcmul($unitPrice, (string) $line->quantity, 2);

                $this->items->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $line->quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $total = bcadd($total, $subtotal, 2);
            }

            $this->orders->update($order->id, ['total_amount' => $total]);

            return $order->refresh()->load('items.product', 'customer');
        });
    }

    /**
     * Guard: only a draft order may be modified.
     */
    public function ensureEditable(Order $order): void
    {
        if ($order->status === OrderStatus::Draft) {
            return;
        }

        $reason = $order->status === OrderStatus::Confirmed
            ? 'Une commande confirmee ne peut plus etre modifiee. Annulez-la '.
              '(POST /orders/{order}/cancel) pour liberer le stock, puis creez-en une nouvelle.'
            : 'Une commande annulee ne peut plus etre modifiee.';

        throw new InvalidOrderTransitionException($order->status, $order->status, $reason);
    }

    /**
     * Update an existing order.
     *
     * Refused on anything but a draft: a confirmed order holds committed
     * stock, and editing it would silently desynchronise available_quantity.
     */
    public function update(Order $order, UpdateOrderDTO $dto): Order
    {
        $this->ensureEditable($order);

        return DB::transaction(function () use ($order, $dto): Order {
            $attributes = $dto->toArray();

            if ($dto->customerPublicId !== null) {
                $customer = $this->customers->findByPublicId($dto->customerPublicId);

                if ($customer === null) {
                    throw new ResourceNotFoundException("Client {$dto->customerPublicId} introuvable.");
                }

                $attributes['customer_id'] = $customer->id;
            }

            if ($attributes === []) {
                return $order;
            }

            return $this->orders->update($order->id, $attributes) ?? $order;
        });
    }

    /**
     * Delete an order and its line items.
     *
     * Line items are removed by the `cascadeOnDelete` foreign key.
     *
     * A confirmed order is refused: deleting it would drop the lines through
     * the cascade and strand the decremented stock with nothing left to
     * restore it from. Cancel it first, which gives the quantities back.
     */
    public function delete(Order $order): bool
    {
        if ($order->status === OrderStatus::Confirmed) {
            throw new InvalidOrderTransitionException(
                $order->status,
                $order->status,
                'Une commande confirmee ne peut pas etre supprimee. Annulez-la '.
                '(POST /orders/{order}/cancel) pour restituer le stock, puis supprimez-la.'
            );
        }

        return DB::transaction(fn (): bool => $this->orders->delete($order->id));
    }

    /**
     * Recompute and persist the order total from its line items.
     */
    public function recalculateTotal(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $total = $order->items()->sum('subtotal');

            $this->orders->update($order->id, ['total_amount' => $total]);

            return $order->refresh();
        });
    }

    /**
     * Confirm a draft order, decrementing the stock of every line item.
     *
     * Availability is checked for ALL lines before anything is written, so a
     * partially servable order never leaves the stock half-decremented. Rows
     * are locked for the duration of the transaction to prevent overselling
     * when two confirmations run at the same time.
     */
    public function confirm(Order $order): Order
    {
        if ($order->status !== OrderStatus::Draft) {
            throw new InvalidOrderTransitionException(
                $order->status,
                OrderStatus::Confirmed,
                "Seule une commande en 'draft' peut etre confirmee (statut actuel : {$order->status->value})."
            );
        }

        return DB::transaction(function () use ($order): Order {
            $lines = $order->items()->with('product')->get();

            if ($lines->isEmpty()) {
                throw new InvalidOrderTransitionException(
                    $order->status,
                    OrderStatus::Confirmed,
                    'Une commande sans ligne ne peut pas etre confirmee.'
                );
            }

            // 1. Verrouiller et cumuler les quantites demandees par produit.
            $requested = [];
            $locked = [];

            foreach ($lines as $line) {
                $productId = $line->product_id;

                $locked[$productId] ??= $this->products->findForUpdate($productId);
                $requested[$productId] = ($requested[$productId] ?? 0) + $line->quantity;
            }

            // 2. Tout verifier avant d'ecrire quoi que ce soit.
            $shortages = [];

            foreach ($requested as $productId => $quantity) {
                $product = $locked[$productId];

                if ($product === null || $product->available_quantity < $quantity) {
                    $shortages[] = [
                        'product' => $product?->name ?? "#$productId",
                        'requested' => $quantity,
                        'available' => $product?->available_quantity ?? 0,
                    ];
                }
            }

            if ($shortages !== []) {
                throw new InsufficientStockException($shortages);
            }

            // 3. Decrementer.
            foreach ($requested as $productId => $quantity) {
                $product = $locked[$productId];

                $this->products->update($productId, [
                    'available_quantity' => $product->available_quantity - $quantity,
                ]);
            }

            $this->orders->update($order->id, ['status' => OrderStatus::Confirmed]);

            return $order->refresh()->load('items.product', 'customer');
        });
    }

    /**
     * Cancel an order, restoring the stock if it had been confirmed.
     */
    public function cancel(Order $order): Order
    {
        if ($order->status === OrderStatus::Cancelled) {
            throw new InvalidOrderTransitionException(
                $order->status,
                OrderStatus::Cancelled,
                'Cette commande est deja annulee.'
            );
        }

        $wasConfirmed = $order->status === OrderStatus::Confirmed;

        return DB::transaction(function () use ($order, $wasConfirmed): Order {
            if ($wasConfirmed) {
                foreach ($order->items()->get() as $line) {
                    $product = $this->products->findForUpdate($line->product_id);

                    if ($product !== null) {
                        $this->products->update($product->id, [
                            'available_quantity' => $product->available_quantity + $line->quantity,
                        ]);
                    }
                }
            }

            $this->orders->update($order->id, ['status' => OrderStatus::Cancelled]);

            return $order->refresh()->load('items.product', 'customer');
        });
    }

    /**
     * Build a unique, human readable order reference.
     */
    private function generateReference(): string
    {
        return 'ORD-'.Str::upper(Str::random(10));
    }
}
