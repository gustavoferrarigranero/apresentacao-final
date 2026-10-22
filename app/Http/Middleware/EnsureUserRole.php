<?php

namespace App\Http\Middleware;

use App\Domains\Users\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica a verificação de acesso ou de contexto de pedido de `EnsureUserRole` antes de a requisição chegar ao controlador.
 */
class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role;

        if (! is_string($role) || ! in_array($role, $roles, true)) {
            return response()->json(['message' => 'Não tem permissão para esta operação.'], 403);
        }

        return $next($request);
    }
}
