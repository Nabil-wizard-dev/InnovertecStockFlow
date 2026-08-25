<?php

use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderItemController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('orders', OrderController::class);

    // Transitions de statut : elles portent des effets sur le stock,
    // donc elles ne passent pas par PATCH /orders/{order}.
    Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])
        ->name('orders.confirm');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])
        ->name('orders.cancel');

    // Les lignes n'existent qu'a travers leur commande.
    Route::apiResource('orders.items', OrderItemController::class)
        ->parameters(['items' => 'item'])
        ->scoped();
});
