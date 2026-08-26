<?php

namespace App\Services\Implementations;

use App\DTOs\Product\CreateProductDTO;
use App\DTOs\Product\UpdateProductDTO;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Services\Interfaces\ProductServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    /**
     * Get a paginated list of products.
     *
     * `$minPrice` / `$maxPrice` bound the unit price, inclusive on both ends.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function list(
        int $perPage = 15,
        ?ProductStatus $status = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
    ): LengthAwarePaginator {
        return $this->repository->paginate($perPage, $status, $minPrice, $maxPrice);
    }

    /**
     * Create a new product.
     */
    public function create(CreateProductDTO $dto): Product
    {
        return DB::transaction(fn (): Product => $this->repository->create($dto->toArray()));
    }

    /**
     * Update an existing product.
     *
     * Note: `status` is never derived from `available_quantity`. The two
     * fields are managed independently, so a stock movement leaves the
     * commercial status untouched.
     */
    public function update(Product $product, UpdateProductDTO $dto): Product
    {
        $attributes = $dto->toArray();

        if ($attributes === []) {
            return $product;
        }

        return DB::transaction(
            fn (): Product => $this->repository->update($product->id, $attributes) ?? $product
        );
    }

    /**
     * Delete a product.
     */
    public function delete(Product $product): bool
    {
        return DB::transaction(fn (): bool => $this->repository->delete($product->id));
    }
}
