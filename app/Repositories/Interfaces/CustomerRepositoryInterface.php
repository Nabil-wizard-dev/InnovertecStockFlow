<?php
namespace App\Repositories\Interfaces;


use App\Models\Customer;

interface CustomerRepositoryInterface
{
    public function find($id);
    
    public function all();

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);
}