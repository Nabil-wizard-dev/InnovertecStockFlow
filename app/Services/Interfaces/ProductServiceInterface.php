<?php

namespace App\Services\Interfaces;

use App\DTOs\Product\CreateProductDTO;
use App\DTOs\Product\UpdateProductDTO;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductServiceInterface
{
    /**
     * Get a paginated list of products.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function list(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new product.
     */
    public function create(CreateProductDTO $dto): Product;

    /**
     * Update an existing product.
     */
    public function update(Product $product, UpdateProductDTO $dto): Product;

    /**
     * Delete a product.
     */
    public function delete(Product $product): bool;
}
