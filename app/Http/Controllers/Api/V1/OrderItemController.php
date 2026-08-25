<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\OrderItem\CreateOrderItemDTO;
use App\DTOs\OrderItem\UpdateOrderItemDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderItem\StoreOrderItemRequest;
use App\Http\Requests\OrderItem\UpdateOrderItemRequest;
use App\Http\Resources\OrderItemResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Interfaces\OrderItemServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Order items', description: 'Lignes d\'une commande')]
class OrderItemController extends Controller
{
    public function __construct(
        private readonly OrderItemServiceInterface $service,
    ) {}

    /**
     * Display the line items of an order.
     */
    #[OA\Get(
        path: '/api/v1/orders/{order}/items',
        summary: 'Lister les lignes d\'une commande',
        tags: ['Order items'],
        parameters: [
            new OA\PathParameter(
                name: 'order',
                description: 'public_id de la commande',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
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
                            items: new OA\Items(ref: '#/components/schemas/OrderItem'),
                        ),
                        new OA\Property(property: 'meta', type: 'object'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Commande introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function index(Request $request, Order $order): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        return ApiResponse::success(
            data: OrderItemResource::collection(
                $this->service->listForOrder($order, max(1, min($perPage, 100)))
            ),
        );
    }

    /**
     * Add a line item to an order.
     */
    #[OA\Post(
        path: '/api/v1/orders/{order}/items',
        summary: 'Ajouter une ligne a une commande',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_id', 'quantity'],
                properties: [
                    new OA\Property(
                        property: 'product_id',
                        type: 'string',
                        description: 'public_id du produit',
                        example: '01m0t7zzrjdzd3jnqhqys4dvjj',
                    ),
                    new OA\Property(property: 'quantity', type: 'integer', example: 3),
                ],
                type: 'object',
            ),
        ),
        tags: ['Order items'],
        parameters: [
            new OA\PathParameter(
                name: 'order',
                description: 'public_id de la commande',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Ligne ajoutee',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Ligne ajoutee avec succes.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/OrderItem'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 409,
                description: 'Commande non modifiable car confirmee ou annulee (INVALID_ORDER_TRANSITION)',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Donnees invalides',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function store(StoreOrderItemRequest $request, Order $order): JsonResponse
    {
        $item = $this->service->create($order, CreateOrderItemDTO::fromRequest($request));

        return ApiResponse::created(
            data: OrderItemResource::make($item),
            message: 'Ligne ajoutee avec succes.',
        );
    }

    /**
     * Display a single line item.
     */
    #[OA\Get(
        path: '/api/v1/orders/{order}/items/{item}',
        summary: 'Afficher une ligne',
        tags: ['Order items'],
        parameters: [
            new OA\PathParameter(name: 'order', required: true, schema: new OA\Schema(type: 'string')),
            new OA\PathParameter(name: 'item', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ligne trouvee',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', nullable: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/OrderItem'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Ligne introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function show(Order $order, OrderItem $item): JsonResponse
    {
        return ApiResponse::success(data: OrderItemResource::make($item->load('product')));
    }

    /**
     * Update a line item.
     */
    #[OA\Patch(
        path: '/api/v1/orders/{order}/items/{item}',
        summary: 'Modifier une ligne',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'product_id', type: 'string', description: 'public_id du produit'),
                    new OA\Property(property: 'quantity', type: 'integer', example: 5),
                ],
                type: 'object',
            ),
        ),
        tags: ['Order items'],
        parameters: [
            new OA\PathParameter(name: 'order', required: true, schema: new OA\Schema(type: 'string')),
            new OA\PathParameter(name: 'item', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ligne modifiee',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Ligne mise a jour avec succes.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/OrderItem'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Ligne introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 409,
                description: 'Commande non modifiable car confirmee ou annulee (INVALID_ORDER_TRANSITION)',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Donnees invalides',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function update(UpdateOrderItemRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        return ApiResponse::success(
            data: OrderItemResource::make(
                $this->service->update($item, UpdateOrderItemDTO::fromRequest($request))
            ),
            message: 'Ligne mise a jour avec succes.',
        );
    }

    /**
     * Remove a line item.
     */
    #[OA\Delete(
        path: '/api/v1/orders/{order}/items/{item}',
        summary: 'Supprimer une ligne',
        tags: ['Order items'],
        parameters: [
            new OA\PathParameter(name: 'order', required: true, schema: new OA\Schema(type: 'string')),
            new OA\PathParameter(name: 'item', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Ligne supprimee'),
            new OA\Response(
                response: 404,
                description: 'Ligne introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 409,
                description: 'Commande non modifiable car confirmee ou annulee (INVALID_ORDER_TRANSITION)',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function destroy(Order $order, OrderItem $item): JsonResponse
    {
        $this->service->delete($item);

        return ApiResponse::noContent();
    }
}
