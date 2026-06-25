<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Ecommerce API',
    version: '1.0.0',
    description: 'Laravel E-commerce REST API — Backend portfolio project'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token'
)]
abstract class Controller
{
    use AuthorizesRequests;
}
