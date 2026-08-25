<?php

namespace App\Enums;

/**
 * Single source of truth for every machine readable error code of the API.
 *
 * Each case knows its conventional HTTP status and a default message, so a
 * new error type is declared once here instead of being spread across
 * controllers, exceptions and the exception handler.
 */
enum ErrorCode: string
{
    // --- 4xx generiques ---
    case ValidationError = 'VALIDATION_ERROR';
    case ResourceNotFound = 'RESOURCE_NOT_FOUND';
    case Unauthenticated = 'UNAUTHENTICATED';
    case Forbidden = 'FORBIDDEN';
    case MethodNotAllowed = 'METHOD_NOT_ALLOWED';
    case TooManyRequests = 'TOO_MANY_REQUESTS';
    case Conflict = 'CONFLICT';

    // --- erreurs metier ---
    case BusinessRule = 'BUSINESS_RULE_VIOLATION';
    case InsufficientStock = 'INSUFFICIENT_STOCK';
    case InvalidOrderTransition = 'INVALID_ORDER_TRANSITION';

    // --- technique ---
    case ServerError = 'SERVER_ERROR';
    case ServiceUnavailable = 'SERVICE_UNAVAILABLE';

    /**
     * The HTTP status conventionally carried by this error.
     */
    public function status(): int
    {
        return match ($this) {
            self::ValidationError => 422,
            self::ResourceNotFound => 404,
            self::Unauthenticated => 401,
            self::Forbidden => 403,
            self::MethodNotAllowed => 405,
            self::TooManyRequests => 429,
            self::Conflict,
            self::BusinessRule,
            self::InsufficientStock,
            self::InvalidOrderTransition => 409,
            self::ServiceUnavailable => 503,
            self::ServerError => 500,
        };
    }

    /**
     * The message used when the thrower does not supply one.
     */
    public function defaultMessage(): string
    {
        return match ($this) {
            self::ValidationError => 'Les donnees fournies sont invalides.',
            self::ResourceNotFound => 'La ressource demandee est introuvable.',
            self::Unauthenticated => 'Authentification requise.',
            self::Forbidden => 'Action non autorisee.',
            self::MethodNotAllowed => 'Methode HTTP non autorisee pour cette route.',
            self::TooManyRequests => 'Trop de requetes, merci de reessayer plus tard.',
            self::Conflict => 'La requete entre en conflit avec l\'etat actuel de la ressource.',
            self::BusinessRule => 'Cette operation est refusee par une regle metier.',
            self::InsufficientStock => 'Stock insuffisant pour satisfaire la demande.',
            self::InvalidOrderTransition => 'Changement de statut impossible pour cette commande.',
            self::ServiceUnavailable => 'Service temporairement indisponible.',
            self::ServerError => 'Une erreur est survenue.',
        };
    }
}
