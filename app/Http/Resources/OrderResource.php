<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Order
 */
#[OA\Schema(
    schema: 'Order',
    title: 'Order',
    description: 'Une commande',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '01m0t7zzrjdzd3jnqhqys4dvjj'),
        new OA\Property(property: 'orderReference', type: 'string', example: 'ORD-4KD82MZQ7X'),
        new OA\Property(property: 'totalAmount', type: 'number', format: 'float', example: 37.5),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['draft', 'confirmed', 'cancelled'],
            example: 'draft',
        ),
        new OA\Property(property: 'customer', ref: '#/components/schemas/Customer', nullable: true),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/OrderItem'),
        ),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class OrderResource extends JsonResource
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
            'orderReference' => $this->order_reference,
            'totalAmount' => (float) $this->total_amount,
            'status' => $this->status->value,
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
