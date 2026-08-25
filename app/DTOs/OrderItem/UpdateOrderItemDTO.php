<?php

namespace App\DTOs\OrderItem;

use App\Http\Requests\OrderItem\UpdateOrderItemRequest;

final readonly class UpdateOrderItemDTO
{
    public function __construct(
        public ?string $productPublicId,
        public ?int $quantity,
    ) {}

    /**
     * Build the DTO from a validated request.
     *
     * A null property means the client did not submit that field.
     */
    public static function fromRequest(UpdateOrderItemRequest $request): self
    {
        $data = $request->validated();

        return new self(
            productPublicId: $data['product_id'] ?? null,
            quantity: array_key_exists('quantity', $data) ? (int) $data['quantity'] : null,
        );
    }

    /**
     * Whether the request carried anything to change.
     */
    public function isEmpty(): bool
    {
        return $this->productPublicId === null && $this->quantity === null;
    }
}
