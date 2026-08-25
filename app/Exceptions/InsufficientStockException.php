<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class InsufficientStockException extends ApiException
{
    /**
     * @param  list<array{product: string, requested: int, available: int}>  $shortages
     */
    public function __construct(
        public readonly array $shortages,
    ) {
        parent::__construct(
            message: 'Stock insuffisant pour confirmer la commande.',
            errors: [
                'items' => array_map(
                    static fn (array $s): string => sprintf(
                        'Produit "%s" : %d demande(s), %d disponible(s).',
                        $s['product'],
                        $s['requested'],
                        $s['available'],
                    ),
                    $shortages,
                ),
            ],
        );
    }

    public function errorCode(): ErrorCode
    {
        return ErrorCode::InsufficientStock;
    }
}
