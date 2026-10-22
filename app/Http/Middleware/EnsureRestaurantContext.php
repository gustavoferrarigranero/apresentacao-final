<?php

namespace App\Http\Middleware;

use App\Domains\Restaurants\Enums\RestaurantStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica a verificação de acesso ou de contexto de pedido de `EnsureRestaurantContext` antes de a requisição chegar ao controlador.
 */
class EnsureRestaurantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $restaurant = $request->user()?->restaurant()->first();

        if ($restaurant === null || $restaurant->status !== RestaurantStatus::ACTIVE->value) {
            return response()->json([
                'message' => 'A conta não está associada a um restaurante ativo.',
            ], 403);
        }

        return $next($request);
    }
}
