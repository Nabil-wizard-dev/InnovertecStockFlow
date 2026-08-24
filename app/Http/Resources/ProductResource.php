<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Product
 */
#[OA\Schema(
    schema: 'Product',
    title: 'Product',
    description: 'Un produit du catalogue',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '01m0t7zzrjdzd3jnqhqys4dvjj'),
        new OA\Property(property: 'name', type: 'string', example: 'Vis M6 inox'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Boite de 100'),
        new OA\Property(property: 'productReference', type: 'string', example: 'REF-VIS-M6'),
        new OA\Property(property: 'unitPrice', type: 'number', format: 'float', example: 12.5),
        new OA\Property(property: 'availableQuantity', type: 'integer', example: 40),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['available', 'out_of_stock', 'awaiting_restock', 'archived'],
            example: 'available',
        ),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'description' => $this->description,
            'productReference' => $this->product_reference,
            'unitPrice' => (float) $this->unit_price,
            'availableQuantity' => $this->available_quantity,
            'status' => $this->status->value,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
