<?php

namespace App\Repositories\Interfaces;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function find($id);

    public function all();

    /**
     * Get a paginated list of products.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);
}
