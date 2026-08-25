<?php

namespace App\Services\Implementations;

use App\DTOs\Customer\CreateCustomerDTO;
use App\DTOs\Customer\UpdateCustomerDTO;
use App\Models\Customer;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Services\Interfaces\CustomerServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService implements CustomerServiceInterface
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository,
    ) {}

    /**
     * Get a paginated list of customers.
     *
     * @return LengthAwarePaginator<int, Customer>
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Create a new customer.
     */
    public function create(CreateCustomerDTO $dto): Customer
    {
        return DB::transaction(fn (): Customer => $this->repository->create($dto->toArray()));
    }

    /**
     * Update an existing customer.
     */
    public function update(Customer $customer, UpdateCustomerDTO $dto): Customer
    {
        $attributes = $dto->toArray();

        if ($attributes === []) {
            return $customer;
        }

        return DB::transaction(
            fn (): Customer => $this->repository->update($customer->id, $attributes) ?? $customer
        );
    }

    /**
     * Delete a customer.
     */
    public function delete(Customer $customer): bool
    {
        return DB::transaction(fn (): bool => $this->repository->delete($customer->id));
    }
}
