<?php

use Illuminate\Support\Facades\Route;

// A versão atual da API está isolada para permitir evoluções sem quebrar os clientes existentes.
Route::prefix('v1')->name('api.v1.')->group(function (): void {
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/restaurants.php';
    require __DIR__.'/api/categories.php';
    require __DIR__.'/api/products.php';
    require __DIR__.'/api/orders.php';
    require __DIR__.'/api/users.php';
});

// Mantém temporariamente os caminhos anteriores enquanto os clientes migram para /api/v1.
require __DIR__.'/api/auth.php';
require __DIR__.'/api/restaurants.php';
require __DIR__.'/api/categories.php';
require __DIR__.'/api/products.php';
require __DIR__.'/api/orders.php';
require __DIR__.'/api/users.php';
