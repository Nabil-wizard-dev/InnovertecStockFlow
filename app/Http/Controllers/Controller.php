<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'stock management API for Innovertec',
    title: 'APIs For stockFlow',
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000',
    description: 'Serveur local',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
)]
abstract class Controller
{
    //
}
