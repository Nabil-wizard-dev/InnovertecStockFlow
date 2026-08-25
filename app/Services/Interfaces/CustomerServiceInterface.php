<?php

namespace App\Services\Interfaces;

use App\DTOs\Customer\CreateCustomerDTO;
use App\DTOs\Customer\UpdateCustomerDTO;
use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomerServiceInterface
{
    /**
     * Get a paginated list of customers.
     *
     * @return LengthAwarePaginator<int, Customer>
     */
    public function list(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new customer.
     */
    public function create(CreateCustomerDTO $dto): Customer;

    /**
     * Update an existing customer.
     */
    public function update(Customer $customer, UpdateCustomerDTO $dto): Customer;

    /**
     * Delete a customer.
     */
    public function delete(Customer $customer): bool;
}
