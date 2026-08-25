<?php

namespace App\DTOs\Order;

final readonly class OrderLineDTO
{
    public function __construct(
        public string $productPublicId,
        public int $quantity,
    ) {}

    /**
     * @param  array<string, mixed>  $line
     */
    public static function fromArray(array $line): self
    {
        return new self(
            productPublicId: $line['product_id'],
            quantity: (int) $line['quantity'],
        );
    }
}
