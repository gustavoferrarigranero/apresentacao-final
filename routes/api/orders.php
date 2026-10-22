<?php

use App\Domains\Users\Enums\UserRole;
use App\Http\Controllers\OrderController;
use App\Http\Middleware\EnsureRestaurantContext;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Support\Facades\Route;

// Operações de pedidos do restaurante, limitadas ao contexto da conta autenticada.
Route::middleware([
    'auth:sanctum',
    EnsureRestaurantContext::class,
    EnsureUserRole::class.':'.UserRole::RESTAURANT_ADMIN->value.','.UserRole::STAFF->value,
])->group(function (): void {
    Route::get('/restaurant/orders', [OrderController::class, 'restaurantIndex']);
    Route::get('/restaurant/orders/{order}', [OrderController::class, 'restaurantShow']);
    Route::patch('/restaurant/orders/{order}/status', [OrderController::class, 'updateStatus']);
});

// Cada cliente só pode consultar, criar ou cancelar os seus próprios pedidos.
Route::middleware(['auth:sanctum', EnsureUserRole::class.':'.UserRole::CUSTOMER->value])->group(function (): void {
    Route::get('/orders', [OrderController::class, 'customerIndex']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'customerShow']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
});
