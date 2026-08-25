<?php
namespace App\Repositories\Implementations;

use App\Models\Customer;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository implements CustomerRepositoryInterface
{ 
   public function find($id)
    {
        return Customer::find($id);
    }

    /**
     * Find a record by its public ULID.
     */
    public function findByPublicId(string $publicId): ?Customer
    {
        return Customer::query()->where('public_id', $publicId)->first();
    }

    public function all()
    {
        return Customer::all();
    }

    /**
     * Get a paginated list of records.
     *
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Customer::query()->latest()->paginate($perPage);
    }

    public function create(array $data)
    {
        return Customer::create($data);
    }

    public function update($id, array $data)
    {
        $customer = Customer::find($id);
        if ($customer) {
            $customer->update($data);
            return $customer;
        }
        return null;
    }

    public function delete($id)
    {
        $customer = Customer::find($id);
        if ($customer) {
            $customer->delete();
            return true;
        }
        return false;
    }
}