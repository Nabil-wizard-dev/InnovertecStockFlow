<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Customer\CreateCustomerDTO;
use App\DTOs\Customer\UpdateCustomerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Services\Interfaces\CustomerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Customers', description: 'Gestion des clients')]
class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerServiceInterface $service,
    ) {}

    /**
     * Display a paginated list of customers.
     */
    #[OA\Get(
        path: '/api/v1/customers',
        summary: 'Lister les clients',
        tags: ['Customers'],
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
                            items: new OA\Items(ref: '#/components/schemas/Customer'),
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

        return ApiResponse::success(
            data: CustomerResource::collection($this->service->list(max(1, min($perPage, 100)))),
        );
    }

    /**
     * Store a newly created customer.
     */
    #[OA\Post(
        path: '/api/v1/customers',
        summary: 'Creer un client',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['first_name', 'last_name', 'email'],
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', example: 'Amina'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Traore'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'amina@example.com'),
                    new OA\Property(property: 'telephone', type: 'string', nullable: true),
                    new OA\Property(property: 'adresse', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        enum: ['active', 'inactive', 'suspended'],
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Customers'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Client cree',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Client cree avec succes.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Customer'),
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
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->service->create(CreateCustomerDTO::fromRequest($request));

        return ApiResponse::created(
            data: CustomerResource::make($customer),
            message: 'Client cree avec succes.',
        );
    }

    /**
     * Display the specified customer.
     */
    #[OA\Get(
        path: '/api/v1/customers/{customer}',
        summary: 'Afficher un client',
        tags: ['Customers'],
        parameters: [
            new OA\PathParameter(
                name: 'customer',
                description: 'public_id du client (ULID)',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client trouve',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', nullable: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Customer'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Client introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function show(Customer $customer): JsonResponse
    {
        return ApiResponse::success(data: CustomerResource::make($customer->load('orders')));
    }

    /**
     * Update the specified customer.
     */
    #[OA\Patch(
        path: '/api/v1/customers/{customer}',
        summary: 'Modifier un client',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'first_name', type: 'string'),
                    new OA\Property(property: 'last_name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'telephone', type: 'string', nullable: true),
                    new OA\Property(property: 'adresse', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        enum: ['active', 'inactive', 'suspended'],
                    ),
                ],
                type: 'object',
            ),
        ),
        tags: ['Customers'],
        parameters: [
            new OA\PathParameter(
                name: 'customer',
                description: 'public_id du client (ULID)',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client modifie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Client mis a jour avec succes.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Customer'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Client introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Donnees invalides',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer = $this->service->update($customer, UpdateCustomerDTO::fromRequest($request));

        return ApiResponse::success(
            data: CustomerResource::make($customer),
            message: 'Client mis a jour avec succes.',
        );
    }

    /**
     * Remove the specified customer.
     */
    #[OA\Delete(
        path: '/api/v1/customers/{customer}',
        summary: 'Supprimer un client',
        tags: ['Customers'],
        parameters: [
            new OA\PathParameter(
                name: 'customer',
                description: 'public_id du client (ULID)',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Client supprime'),
            new OA\Response(
                response: 404,
                description: 'Client introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function destroy(Customer $customer): JsonResponse
    {
        $this->service->delete($customer);

        return ApiResponse::noContent();
    }
}
