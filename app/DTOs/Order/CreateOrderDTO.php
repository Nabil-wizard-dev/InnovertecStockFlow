<?php

namespace App\DTOs\Order;

use App\Enums\OrderStatus;
use App\Http\Requests\Order\StoreOrderRequest;

final readonly class CreateOrderDTO
{
    /**
     * @param  list<OrderLineDTO>  $items
     */
    public function __construct(
        public string $customerPublicId,
        public ?string $orderReference,
        public OrderStatus $status,
        public array $items,
    ) {}

    /**
     * Build the DTO from a validated request.
     */
    public static function fromRequest(StoreOrderRequest $request): self
    {
        $data = $request->validated();

        return new self(
            customerPublicId: $data['customer_id'],
            orderReference: $data['order_reference'] ?? null,
            status: OrderStatus::from($data['status'] ?? OrderStatus::Draft->value),
            items: array_map(
                static fn (array $line): OrderLineDTO => OrderLineDTO::fromArray($line),
                $data['items'] ?? [],
            ),
        );
    }
}
