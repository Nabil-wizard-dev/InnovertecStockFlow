<?php

namespace App\Repositories\Interfaces;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function find($id);

    /**
     * Find a record by its public ULID.
     */
    public function findByPublicId(string $publicId): ?Product;

    /**
     * Find a record and lock the row until the transaction ends.
     *
     * Used before adjusting stock so two concurrent confirmations cannot
     * both read the same quantity and oversell.
     */
    public function findForUpdate(int $id): ?Product;

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
