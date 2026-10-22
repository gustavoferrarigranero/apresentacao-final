<?php

use App\Domains\Users\Enums\UserRole;
use App\Http\Controllers\RestaurantUserController;
use App\Http\Middleware\EnsureRestaurantContext;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Support\Facades\Route;

// A administração do restaurante pode criar membros da sua própria equipa.
Route::middleware([
    'auth:sanctum',
    EnsureRestaurantContext::class,
    EnsureUserRole::class.':'.UserRole::RESTAURANT_ADMIN->value,
])->post('/restaurant/users', [RestaurantUserController::class, 'store']);
