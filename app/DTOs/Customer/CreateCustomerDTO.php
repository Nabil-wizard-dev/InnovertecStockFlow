<?php

namespace App\DTOs\Customer;

use App\Enums\CustomerStatus;
use App\Http\Requests\Customer\StoreCustomerRequest;

final readonly class CreateCustomerDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $telephone,
        public ?string $adresse,
        public CustomerStatus $status,
    ) {}

    /**
     * Build the DTO from a validated request.
     */
    public static function fromRequest(StoreCustomerRequest $request): self
    {
        $data = $request->validated();

        return new self(
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            email: $data['email'],
            telephone: $data['telephone'] ?? null,
            adresse: $data['adresse'] ?? null,
            status: CustomerStatus::from($data['status'] ?? CustomerStatus::Active->value),
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
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'status' => $this->status,
        ];
    }
}
