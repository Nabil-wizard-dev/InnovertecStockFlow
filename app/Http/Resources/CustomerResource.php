<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Customer
 */
#[OA\Schema(
    schema: 'Customer',
    title: 'Customer',
    description: 'Un client',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '01m0t7zzrjdzd3jnqhqys4dvjj'),
        new OA\Property(property: 'firstName', type: 'string', example: 'Amina'),
        new OA\Property(property: 'lastName', type: 'string', example: 'Traore'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'amina@example.com'),
        new OA\Property(property: 'telephone', type: 'string', nullable: true, example: '+225 07 00 00 00'),
        new OA\Property(property: 'adresse', type: 'string', nullable: true, example: 'Abidjan, Cocody'),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['active', 'inactive', 'suspended'],
            example: 'active',
        ),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
    ],
    type: 'object',
)]
class CustomerResource extends JsonResource
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
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'status' => $this->status->value,
            'orders' => OrderResource::collection($this->whenLoaded('orders')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
