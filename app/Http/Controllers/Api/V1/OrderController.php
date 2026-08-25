<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\UpdateOrderDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Services\Interfaces\OrderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Orders', description: 'Gestion des commandes')]
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderServiceInterface $service,
    ) {}

    /**
     * Display a paginated list of orders.
     */
    #[OA\Get(
        path: '/api/v1/orders',
        summary: 'Lister les commandes',
        tags: ['Orders'],
        parameters: [
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
                            items: new OA\Items(ref: '#/components/schemas/Order'),
                        ),
                        new OA\Property(property: 'meta', type: 'object'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);

        $orders = $this->service->list(max(1, min($perPage, 100)));
        $orders->load('customer', 'items.product');

        return ApiResponse::success(data: OrderResource::collection($orders));
    }

    /**
     * Store a newly created order with its line items.
     */
    #[OA\Post(
        path: '/api/v1/orders',
        summary: 'Creer une commande',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customer_id'],
                properties: [
                    new OA\Property(
                        property: 'customer_id',
                        type: 'string',
                        description: 'public_id du client',
                        example: '01m0t7zzrjdzd3jnqhqys4dvjj',
                    ),
                    new OA\Property(
                        property: 'order_reference',
                        type: 'string',
                        description: 'Genere automatiquement si absent',
                    ),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            required: ['product_id', 'quantity'],
                            properties: [
                                new OA\Property(
                                    property: 'product_id',
                                    type: 'string',
                                    description: 'public_id du produit',
                                ),
                                new OA\Property(property: 'quantity', type: 'integer', example: 3),
                            ],
                            type: 'object',
                        ),
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Orders'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Commande creee',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Commande creee avec succes.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
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
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->service->create(CreateOrderDTO::fromRequest($request));

        return ApiResponse::created(
            data: OrderResource::make($order),
            message: 'Commande creee avec succes.',
        );
    }

    /**
     * Display the specified order.
     */
    #[OA\Get(
        path: '/api/v1/orders/{order}',
        summary: 'Afficher une commande',
        tags: ['Orders'],
        parameters: [
            new OA\PathParameter(
                name: 'order',
                description: 'public_id de la commande (ULID)',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Commande trouvee',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', nullable: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
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
    public function show(Order $order): JsonResponse
    {
        return ApiResponse::success(
            data: OrderResource::make($order->load('customer', 'items.product')),
        );
    }

    /**
     * Update the specified order.
     */
    #[OA\Patch(
        path: '/api/v1/orders/{order}',
        summary: 'Modifier une commande',
        description: 'Le statut ne se change pas ici : utiliser /confirm ou /cancel.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'customer_id', type: 'string', description: 'public_id du client'),
                    new OA\Property(property: 'order_reference', type: 'string'),
                ],
                type: 'object',
            ),
        ),
        tags: ['Orders'],
        parameters: [
            new OA\PathParameter(
                name: 'order',
                description: 'public_id de la commande (ULID)',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Commande modifiee',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Commande mise a jour avec succes.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Commande introuvable',
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
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $order = $this->service->update($order, UpdateOrderDTO::fromRequest($request));

        return ApiResponse::success(
            data: OrderResource::make($order->load('customer', 'items.product')),
            message: 'Commande mise a jour avec succes.',
        );
    }

    /**
     * Remove the specified order.
     */
    #[OA\Delete(
        path: '/api/v1/orders/{order}',
        summary: 'Supprimer une commande',
        tags: ['Orders'],
        parameters: [
            new OA\PathParameter(
                name: 'order',
                description: 'public_id de la commande (ULID)',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Commande supprimee'),
            new OA\Response(
                response: 404,
                description: 'Commande introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 409,
                description: 'Commande confirmee : annuler d\'abord pour restituer le stock (INVALID_ORDER_TRANSITION)',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function destroy(Order $order): JsonResponse
    {
        $this->service->delete($order);

        return ApiResponse::noContent();
    }

    /**
     * Confirm a draft order and decrement the stock.
     */
    #[OA\Post(
        path: '/api/v1/orders/{order}/confirm',
        summary: 'Confirmer une commande (decremente le stock)',
        description: 'Verifie la disponibilite de toutes les lignes, decremente '.
            'available_quantity de chaque produit, puis passe la commande en '.
            'confirmed. Si une seule ligne est non servable, rien n\'est ecrit.',
        tags: ['Orders'],
        parameters: [
            new OA\PathParameter(
                name: 'order',
                description: 'public_id de la commande (ULID)',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Commande confirmee',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Commande confirmee avec succes.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Commande introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 409,
                description: 'Stock insuffisant (INSUFFICIENT_STOCK) ou statut incompatible (INVALID_ORDER_TRANSITION)',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function confirm(Order $order): JsonResponse
    {
        return ApiResponse::success(
            data: OrderResource::make($this->service->confirm($order)),
            message: 'Commande confirmee avec succes.',
        );
    }

    /**
     * Cancel an order, restoring the stock when it was confirmed.
     */
    #[OA\Post(
        path: '/api/v1/orders/{order}/cancel',
        summary: 'Annuler une commande (restitue le stock si confirmee)',
        description: 'Passe la commande en cancelled. Si elle etait confirmed, '.
            'les quantites sont rendues au stock.',
        tags: ['Orders'],
        parameters: [
            new OA\PathParameter(
                name: 'order',
                description: 'public_id de la commande (ULID)',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Commande annulee',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Commande annulee avec succes.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Commande introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 409,
                description: 'Commande deja annulee (INVALID_ORDER_TRANSITION)',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function cancel(Order $order): JsonResponse
    {
        return ApiResponse::success(
            data: OrderResource::make($this->service->cancel($order)),
            message: 'Commande annulee avec succes.',
        );
    }
}
