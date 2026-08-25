<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Product\CreateProductDTO;
use App\DTOs\Product\UpdateProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\IndexProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Product;
use App\Services\Interfaces\ProductServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Products', description: 'Gestion du catalogue produits')]
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductServiceInterface $service,
    ) {}

    /**
     * Display a paginated list of products.
     */
    #[OA\Get(
        path: '/api/v1/products',
        summary: 'Lister les produits',
        tags: ['Products'],
        parameters: [
            new OA\QueryParameter(
                name: 'status',
                description: 'Filtre sur le statut du produit',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['available', 'out_of_stock', 'awaiting_restock', 'archived'],
                ),
            ),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginee',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', nullable: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Product'),
                        ),
                        new OA\Property(property: 'meta', type: 'object'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 422,
                description: 'Statut inconnu',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function index(IndexProductRequest $request): JsonResponse
    {
        return ApiResponse::success(
            data: ProductResource::collection(
                $this->service->list($request->perPage(), $request->status())
            ),
        );
    }

    /**
     * Store a newly created product.
     */
    #[OA\Post(
        path: '/api/v1/products',
        summary: 'Creer un produit',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'product_reference', 'unit_price', 'available_quantity'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Vis M6 inox'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Boite de 100'),
                    new OA\Property(property: 'product_reference', type: 'string', example: 'REF-VIS-M6'),
                    new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 12.5),
                    new OA\Property(property: 'available_quantity', type: 'integer', example: 40),
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        enum: ['available', 'out_of_stock', 'awaiting_restock', 'archived'],
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Produit cree',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Produit cree avec succes.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 422,
                description: 'Donnees invalides',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->create(CreateProductDTO::fromRequest($request));

        return ApiResponse::created(
            data: ProductResource::make($product),
            message: 'Produit cree avec succes.',
        );
    }

    /**
     * Display the specified product.
     */
    #[OA\Get(
        path: '/api/v1/products/{product}',
        summary: 'Afficher un produit',
        tags: ['Products'],
        parameters: [
            new OA\PathParameter(
                name: 'product',
                description: 'public_id du produit (ULID)',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Produit trouve',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', nullable: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Produit introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function show(Product $product): JsonResponse
    {
        return ApiResponse::success(data: ProductResource::make($product));
    }

    /**
     * Update the specified product.
     */
    #[OA\Patch(
        path: '/api/v1/products/{product}',
        summary: 'Modifier un produit',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'product_reference', type: 'string'),
                    new OA\Property(property: 'unit_price', type: 'number', format: 'float'),
                    new OA\Property(property: 'available_quantity', type: 'integer'),
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        enum: ['available', 'out_of_stock', 'awaiting_restock', 'archived'],
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Products'],
        parameters: [
            new OA\PathParameter(
                name: 'product',
                description: 'public_id du produit (ULID)',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Produit modifie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Produit mis a jour avec succes.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Produit introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Donnees invalides',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->service->update($product, UpdateProductDTO::fromRequest($request));

        return ApiResponse::success(
            data: ProductResource::make($product),
            message: 'Produit mis a jour avec succes.',
        );
    }

    /**
     * Remove the specified product.
     */
    #[OA\Delete(
        path: '/api/v1/products/{product}',
        summary: 'Supprimer un produit',
        tags: ['Products'],
        parameters: [
            new OA\PathParameter(
                name: 'product',
                description: 'public_id du produit (ULID)',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Produit supprime'),
            new OA\Response(
                response: 404,
                description: 'Produit introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function destroy(Product $product): JsonResponse
    {
        $this->service->delete($product);

        return ApiResponse::noContent();
    }
}
