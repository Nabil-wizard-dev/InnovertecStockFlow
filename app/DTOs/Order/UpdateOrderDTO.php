<?php

namespace App\DTOs\Order;

use App\Http\Requests\Order\UpdateOrderRequest;

final readonly class UpdateOrderDTO
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    private function __construct(
        public array $attributes,
        public ?string $customerPublicId,
    ) {}

    /**
     * Build the DTO from a validated request.
     *
     * Only the keys actually submitted are kept, so a PATCH never
     * overwrites a field the client did not send.
     */
    public static function fromRequest(UpdateOrderRequest $request): self
    {
        $data = $request->validated();
        $attributes = [];

        if (array_key_exists('order_reference', $data)) {
            $attributes['order_reference'] = $data['order_reference'];
        }

        return new self($attributes, $data['customer_id'] ?? null);
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
