<?php

namespace App\DTOs\Product;

use App\Enums\ProductStatus;
use App\Http\Requests\Product\StoreProductRequest;

final readonly class CreateProductDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
        public string $productReference,
        public float $unitPrice,
        public int $availableQuantity,
        public ProductStatus $status,
    ) {}

    /**
     * Build the DTO from a validated request.
     */
    public static function fromRequest(StoreProductRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            productReference: $data['product_reference'],
            unitPrice: (float) $data['unit_price'],
            availableQuantity: (int) $data['available_quantity'],
            status: ProductStatus::from($data['status'] ?? ProductStatus::Available->value),
        );
    }

    /**
     * Convert the DTO to the column map expected by the repository.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'product_reference' => $this->productReference,
            'unit_price' => $this->unitPrice,
            'available_quantity' => $this->availableQuantity,
            'status' => $this->status,
        ];
    }
}
