<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use App\Enums\OrderStatus;

class InvalidOrderTransitionException extends ApiException
{
    public function __construct(
        public readonly OrderStatus $from,
        public readonly OrderStatus $to,
        string $reason = '',
    ) {
        $message = $reason !== ''
            ? $reason
            : sprintf('Transition impossible : %s -> %s.', $from->value, $to->value);

        parent::__construct(
            message: $message,
            errors: ['status' => [$message]],
        );
    }

    public function errorCode(): ErrorCode
    {
        return ErrorCode::InvalidOrderTransition;
    }
}
