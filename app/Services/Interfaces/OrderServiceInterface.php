<?php

namespace App\Services\Interfaces;

use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\UpdateOrderDTO;
use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderServiceInterface
{
    /**
     * Get a paginated list of orders.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function list(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new order along with its line items.
     */
    public function create(CreateOrderDTO $dto): Order;

    /**
     * Update an existing order.
     */
    public function update(Order $order, UpdateOrderDTO $dto): Order;

    /**
     * Delete an order and its line items.
     */
    public function delete(Order $order): bool;

    /**
     * Recompute and persist the order total from its line items.
     */
    public function recalculateTotal(Order $order): Order;

    /**
     * Guard: only a draft order may be modified.
     *
     * Once confirmed, an order holds committed stock; once cancelled, it is
     * an archive. Both are frozen, so the rule lives here and is enforced by
     * the order service and the order item service alike.
     *
     * @throws \App\Exceptions\InvalidOrderTransitionException
     */
    public function ensureEditable(Order $order): void;

    /**
     * Confirm a draft order, decrementing the stock of every line item.
     *
     * @throws \App\Exceptions\InsufficientStockException
     * @throws \App\Exceptions\InvalidOrderTransitionException
     */
    public function confirm(Order $order): Order;

    /**
     * Cancel an order, restoring the stock if it had been confirmed.
     *
     * @throws \App\Exceptions\InvalidOrderTransitionException
     */
    public function cancel(Order $order): Order;
}
