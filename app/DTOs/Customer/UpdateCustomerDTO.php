<?php

namespace App\DTOs\Customer;

use App\Enums\CustomerStatus;
use App\Http\Requests\Customer\UpdateCustomerRequest;

final readonly class UpdateCustomerDTO
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
    public static function fromRequest(UpdateCustomerRequest $request): self
    {
        $data = $request->validated();
        $attributes = [];

        foreach (['first_name', 'last_name', 'email', 'telephone', 'adresse'] as $column) {
            if (array_key_exists($column, $data)) {
                $attributes[$column] = $data[$column];
            }
        }

        if (array_key_exists('status', $data)) {
            $attributes['status'] = CustomerStatus::from($data['status']);
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
