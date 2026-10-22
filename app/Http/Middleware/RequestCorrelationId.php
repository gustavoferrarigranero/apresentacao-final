<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Associa cada pedido a um identificador seguro para correlacionar respostas e logs.
 */
final class RequestCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        // Reutiliza apenas UUIDs válidos; valores arbitrários do cliente não entram no contexto dos logs.
        $requestId = (string) $request->header('X-Request-Id');
        if (! Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid();
        }

        Log::withoutContext();
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
