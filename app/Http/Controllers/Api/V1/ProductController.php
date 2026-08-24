<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Product\CreateProductDTO;
use App\DTOs\Product\UpdateProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\Interfaces\ProductServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
                name: 'page',
                description: 'Numero de page',
                schema: new OA\Schema(type: 'integer', default: 1),
            ),
            new OA\QueryParameter(
                name: 'per_page',
                description: 'Elements par page (1 a 100)',
                schema: new OA\Schema(type: 'integer', default: 15),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginee',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Product'),
                        ),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->integer('per_page', 15);

        return ProductResource::collection(
            $this->service->list(max(1, min($perPage, 100)))
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 422, description: 'Donnees invalides'),
        ],
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->service->create(CreateProductDTO::fromRequest($request));

        return ProductResource::make($product)
            ->response()
            ->setStatusCode(201);
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'Produit introuvable'),
        ],
    )]
    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product);
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'Produit introuvable'),
            new OA\Response(response: 422, description: 'Donnees invalides'),
        ],
    )]
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        return ProductResource::make(
            $this->service->update($product, UpdateProductDTO::fromRequest($request))
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
            new OA\Response(response: 404, description: 'Produit introuvable'),
        ],
    )]
    public function destroy(Product $product): JsonResponse
    {
        $this->service->delete($product);

        return response()->json(status: 204);
    }
}
