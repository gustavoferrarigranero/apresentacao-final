<?php

use App\Domains\Users\Enums\UserRole;
use App\Http\Controllers\CategoryController;
use App\Http\Middleware\EnsureRestaurantContext;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Support\Facades\Route;

// Categorias são sempre consultadas e geridas dentro do restaurante da conta autenticada.
Route::middleware([
    'auth:sanctum',
    EnsureRestaurantContext::class,
    EnsureUserRole::class.':'.UserRole::RESTAURANT_ADMIN->value.','.UserRole::STAFF->value,
])->group(function (): void {
    Route::apiResource('categories', CategoryController::class);
});
