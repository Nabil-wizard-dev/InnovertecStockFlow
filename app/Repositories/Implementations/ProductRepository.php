<?php
namespace App\Repositories\Implementations;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    public function find($id)
    {
        return Product::find($id);
    }

    /**
     * Find a record by its public ULID.
     */
    public function findByPublicId(string $publicId): ?Product
    {
        return Product::query()->where('public_id', $publicId)->first();
    }

    /**
     * Find a record and lock the row until the transaction ends.
     */
    public function findForUpdate(int $id): ?Product
    {
        return Product::query()->lockForUpdate()->find($id);
    }

    public function all()
    {
        return Product::all();
    }

    /**
     * Get a paginated list of products.
     *
     * `$minPrice` / `$maxPrice` bound the unit price, inclusive on both ends.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(
        int $perPage = 15,
        ?ProductStatus $status = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
    ): LengthAwarePaginator {
        return Product::query()
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($minPrice !== null, fn ($query) => $query->where('unit_price', '>=', $minPrice))
            ->when($maxPrice !== null, fn ($query) => $query->where('unit_price', '<=', $maxPrice))
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data)
    {
        return Product::create($data);
    }

    public function update($id, array $data)
    {
        $product = Product::find($id);
        if ($product) {
            $product->update($data);
            return $product;
        }
        return null;
    }

    public function delete($id)
    {
        $product = Product::find($id);
        if ($product) {
            $product->delete();
            return true;
        }
        return false;
    }
}