<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

/**
 * Generic business rule violation, for the cases that do not deserve a
 * dedicated class yet.
 *
 * Prefer a named subclass of ApiException as soon as the rule is raised from
 * more than one place, or when the client needs to branch on the code.
 */
class BusinessRuleException extends ApiException
{
    public function errorCode(): ErrorCode
    {
        return ErrorCode::BusinessRule;
    }
}
