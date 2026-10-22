<?php

use App\Domains\Users\Enums\UserRole;
use App\Http\Controllers\RestaurantController;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Support\Facades\Route;

// A consulta pública alimenta a listagem e a página de detalhe no front-end.
Route::get('/restaurants', [RestaurantController::class, 'index']);
Route::get('/restaurants/{restaurant}', [RestaurantController::class, 'show']);

// Apenas a administração global pode registar um novo restaurante.
Route::middleware(['auth:sanctum', EnsureUserRole::class.':'.UserRole::SUPER_ADMIN->value])
    ->post('/restaurants', [RestaurantController::class, 'store']);

// A autenticação é obrigatória para gerir dados de um restaurante existente.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::put('/restaurants/{restaurant}', [RestaurantController::class, 'update']);
    Route::patch('/restaurants/{restaurant}', [RestaurantController::class, 'update']);
    Route::delete('/restaurants/{restaurant}', [RestaurantController::class, 'destroy']);
});
