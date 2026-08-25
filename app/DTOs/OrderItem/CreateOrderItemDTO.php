<?php

namespace App\DTOs\OrderItem;

use App\Http\Requests\OrderItem\StoreOrderItemRequest;

final readonly class CreateOrderItemDTO
{
    public function __construct(
        public string $productPublicId,
        public int $quantity,
    ) {}

    /**
     * Build the DTO from a validated request.
     */
    public static function fromRequest(StoreOrderItemRequest $request): self
    {
        $data = $request->validated();

        return new self(
            productPublicId: $data['product_id'],
            quantity: (int) $data['quantity'],
        );
    }
}
