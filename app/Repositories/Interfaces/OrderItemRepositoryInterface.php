<?php
namespace App\Repositories\Interfaces;

use App\Models\OrderItem;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderItemRepositoryInterface
{
    public function find($id);
    
    public function all();

    /**
     * Get a paginated list of records.
     *
     * @return LengthAwarePaginator<int, OrderItem>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);
}