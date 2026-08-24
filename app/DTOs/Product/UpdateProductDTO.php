<?php

namespace App\DTOs\Product;

use App\Enums\ProductStatus;
use App\Http\Requests\Product\UpdateProductRequest;

final readonly class UpdateProductDTO
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    private function __construct(
        public array $attributes,
    ) {}

    /**
     * Build the DTO from a validated request.
     *
     * Only the keys actually submitted are kept, so a PATCH never
     * overwrites a field the client did not send.
     */
    public static function fromRequest(UpdateProductRequest $request): self
    {
        $data = $request->validated();
        $attributes = [];

        foreach ([
            'name' => 'name',
            'description' => 'description',
            'product_reference' => 'product_reference',
            'unit_price' => 'unit_price',
            'available_quantity' => 'available_quantity',
        ] as $input => $column) {
            if (array_key_exists($input, $data)) {
                $attributes[$column] = $data[$input];
            }
        }

        if (array_key_exists('status', $data)) {
            $attributes['status'] = ProductStatus::from($data['status']);
        }

        return new self($attributes);
    }

    /**
     * Convert the DTO to the column map expected by the repository.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
