<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

/**
 * Base class for every error the application raises deliberately.
 *
 * A subclass only declares which ErrorCode it carries; the HTTP status, the
 * default message and the JSON rendering all come from here, so a new
 * business error costs a handful of lines and can never drift from the
 * common envelope.
 */
abstract class ApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $errors  field => list of messages
     */
    public function __construct(
        string $message = '',
        protected array $errors = [],
        protected ?int $statusOverride = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $message !== '' ? $message : $this->errorCode()->defaultMessage(),
            0,
            $previous,
        );
    }

    /**
     * The machine readable code this exception carries.
     */
    abstract public function errorCode(): ErrorCode;

    /**
     * The HTTP status of the response.
     */
    public function status(): int
    {
        return $this->statusOverride ?? $this->errorCode()->status();
    }

    /**
     * Field level details, when there are any.
     *
     * @return array<string, mixed>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Render the exception through the shared envelope.
     *
     * Laravel calls this automatically, so no handler registration is needed
     * for any subclass.
     */
    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error(
            message: $this->getMessage(),
            errorCode: $this->errorCode(),
            status: $this->status(),
            errors: $this->errors(),
        );
    }
}
