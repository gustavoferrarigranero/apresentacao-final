<?php

use App\Domains\Users\Enums\UserRole;
use App\Http\Controllers\ProdutController;
use App\Http\Middleware\EnsureRestaurantContext;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Support\Facades\Route;

// Catálogo público: o identificador do restaurante delimita os produtos visíveis.
Route::get('/restaurants/{restaurant}/products', [ProdutController::class, 'index']);
Route::get('/restaurants/{restaurant}/products/{product}', [ProdutController::class, 'show']);

// A gestão exige uma conta de restaurante ativa com o perfil adequado.
Route::middleware([
    'auth:sanctum',
    EnsureRestaurantContext::class,
    EnsureUserRole::class.':'.UserRole::RESTAURANT_ADMIN->value.','.UserRole::STAFF->value,
])->group(function (): void {
    Route::post('/products', [ProdutController::class, 'store']);
    Route::put('/products/{product}', [ProdutController::class, 'update']);
    Route::patch('/products/{product}', [ProdutController::class, 'update']);
    Route::delete('/products/{product}', [ProdutController::class, 'destroy']);
});
