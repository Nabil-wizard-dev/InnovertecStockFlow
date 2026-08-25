<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin OrderItem
 */
#[OA\Schema(
    schema: 'OrderItem',
    title: 'OrderItem',
    description: 'Une ligne de commande',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '01m0t7zzrjdzd3jnqhqys4dvjj'),
        new OA\Property(property: 'quantity', type: 'integer', example: 3),
        new OA\Property(property: 'unitPrice', type: 'number', format: 'float', example: 12.5),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 37.5),
        new OA\Property(property: 'product', ref: '#/components/schemas/Product', nullable: true),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class OrderItemResource extends JsonResource
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
            'quantity' => $this->quantity,
            'unitPrice' => (float) $this->unit_price,
            'subtotal' => (float) $this->subtotal,
            'product' => ProductResource::make($this->whenLoaded('product')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
