<?php
namespace App\Repositories\Implementations;

use App\Models\Customer;
use App\Repositories\Interfaces\CustomerRepositoryInterface;

class CustomerRepository implements CustomerRepositoryInterface
{ 
   public function find($id)
    {
        return Customer::find($id);
    }

    public function all()
    {
        return Customer::all();
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