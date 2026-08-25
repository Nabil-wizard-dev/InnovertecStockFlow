<?php

namespace App\Http\Responses;

use App\Enums\ErrorCode;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Single entry point for every JSON response of the API.
 *
 * Success envelope:
 *   { "success": true, "message": string|null, "data": mixed,
 *     "meta"?: object, "links"?: object }
 *
 * Error envelope:
 *   { "success": false, "message": string, "error_code": string,
 *     "errors"?: object }
 *
 * The taxonomy of error codes lives in {@see ErrorCode}; the exceptions that
 * carry them derive from {@see \App\Exceptions\ApiException}.
 */
#[OA\Schema(
    schema: 'ErrorResponse',
    title: 'ErrorResponse',
    description: 'Enveloppe commune a toutes les reponses en erreur',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'La ressource demandee est introuvable.'),
        new OA\Property(
            property: 'error_code',
            type: 'string',
            enum: [
                'VALIDATION_ERROR', 'RESOURCE_NOT_FOUND', 'UNAUTHENTICATED', 'FORBIDDEN',
                'METHOD_NOT_ALLOWED', 'TOO_MANY_REQUESTS', 'CONFLICT', 'BUSINESS_RULE_VIOLATION',
                'INSUFFICIENT_STOCK', 'INVALID_ORDER_TRANSITION', 'SERVER_ERROR', 'SERVICE_UNAVAILABLE',
            ],
            example: 'RESOURCE_NOT_FOUND',
        ),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
            nullable: true,
        ),
    ],
    type: 'object',
)]
final class ApiResponse
{
    /**
     * Return a successful response.
     *
     * `$data` may be a JsonResource, a paginated resource collection, an
     * Arrayable, a plain array or a scalar. Pagination metadata carried by a
     * resource collection is lifted into `meta` / `links` instead of lost.
     *
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $headers
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        [$payload, $extractedMeta, $links] = self::extract($data);

        $body = [
            'success' => true,
            'message' => $message,
            'data' => $payload,
        ];

        $meta = array_merge($extractedMeta, $meta);

        if ($meta !== []) {
            $body['meta'] = $meta;
        }

        if ($links !== []) {
            $body['links'] = $links;
        }

        return response()->json($body, $status, $headers);
    }

    /**
     * Return a 201 response for a freshly created resource.
     *
     * @param  array<string, string>  $headers
     */
    public static function created(
        mixed $data = null,
        ?string $message = null,
        array $headers = [],
    ): JsonResponse {
        return self::success($data, $message, 201, headers: $headers);
    }

    /**
     * Return a 204 response, which carries no body by definition.
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(status: 204);
    }

    /**
     * Return an error response.
     *
     * When `$status` is omitted it is derived from the error code, so callers
     * never have to remember that a stock shortage is a 409.
     *
     * @param  array<string, mixed>  $errors  field => list of messages
     * @param  array<string, string>  $headers
     */
    public static function error(
        string|ErrorCode|null $message = null,
        ErrorCode|string|null $errorCode = null,
        ?int $status = null,
        array $errors = [],
        array $headers = [],
    ): JsonResponse {
        // Permet ApiResponse::error(ErrorCode::ResourceNotFound) en raccourci.
        if ($message instanceof ErrorCode) {
            $errorCode ??= $message;
            $message = null;
        }

        $code = $errorCode instanceof ErrorCode
            ? $errorCode
            : (is_string($errorCode) ? ErrorCode::tryFrom($errorCode) : null);

        $body = [
            'success' => false,
            'message' => $message
                ?? $code?->defaultMessage()
                ?? ErrorCode::ServerError->defaultMessage(),
            'error_code' => $code?->value
                ?? (is_string($errorCode) ? $errorCode : ErrorCode::ServerError->value),
        ];

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status ?? $code?->status() ?? 400, $headers);
    }

    /**
     * Return a 422 response for validation failures.
     *
     * @param  array<string, mixed>  $errors
     */
    public static function validationError(
        array $errors,
        ?string $message = null,
    ): JsonResponse {
        return self::error($message, ErrorCode::ValidationError, errors: $errors);
    }

    /**
     * Return a 404 response.
     */
    public static function notFound(?string $message = null): JsonResponse
    {
        return self::error($message, ErrorCode::ResourceNotFound);
    }

    /**
     * Split the payload, the pagination metadata and the navigation links
     * that a resource collection may carry.
     *
     * Laravel nests pagination under `meta` and repeats a verbose list of
     * page buttons in `meta.links`; both are flattened here so the envelope
     * stays predictable: `meta` holds counters, `links` holds URLs.
     *
     * @return array{0: mixed, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private static function extract(mixed $data): array
    {
        if ($data === null) {
            return [null, [], []];
        }

        if ($data instanceof JsonResource) {
            $payload = $data->response()->getData(true);

            $extracted = $payload['data'] ?? $payload;

            $meta = $payload['meta'] ?? [];
            unset($meta['links']);

            $links = $payload['links'] ?? [];

            return [$extracted, $meta, $links];
        }

        if ($data instanceof Arrayable) {
            return [$data->toArray(), [], []];
        }

        return [$data, [], []];
    }
}
