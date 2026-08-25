<?php
namespace App\Repositories\Interfaces;


use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface
{
    public function find($id);

    /**
     * Find a record by its public ULID.
     */
    public function findByPublicId(string $publicId): ?Customer;
    
    public function all();

    /**
     * Get a paginated list of records.
     *
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);
}