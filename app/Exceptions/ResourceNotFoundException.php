<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class ResourceNotFoundException extends ApiException
{
    public function errorCode(): ErrorCode
    {
        return ErrorCode::ResourceNotFound;
    }

    /**
     * Build the exception for a resource missed by its public identifier.
     */
    public static function for(string $resource, string $identifier): self
    {
        return new self(sprintf('%s "%s" introuvable.', $resource, $identifier));
    }
}
