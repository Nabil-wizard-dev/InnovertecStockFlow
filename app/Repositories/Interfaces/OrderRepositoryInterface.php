<?php 
namespace App\Repositories\Interfaces;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function find($id);
    
    public function all();

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);
}