<?php

namespace App\Services\Interfaces;

use App\DTOs\OrderItem\CreateOrderItemDTO;
use App\DTOs\OrderItem\UpdateOrderItemDTO;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderItemServiceInterface
{
    /**
     * Get a paginated list of the line items of an order.
     *
     * @return LengthAwarePaginator<int, OrderItem>
     */
    public function listForOrder(Order $order, int $perPage = 15): LengthAwarePaginator;

    /**
     * Add a line item to an order.
     */
    public function create(Order $order, CreateOrderItemDTO $dto): OrderItem;

    /**
     * Update a line item.
     */
    public function update(OrderItem $item, UpdateOrderItemDTO $dto): OrderItem;

    /**
     * Remove a line item from its order.
     */
    public function delete(OrderItem $item): bool;
}
