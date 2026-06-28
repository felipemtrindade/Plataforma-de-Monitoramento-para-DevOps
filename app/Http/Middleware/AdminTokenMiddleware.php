<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Token de acesso ausente.'], 401);
        }

        $user = User::query()
            ->where('api_token', hash('sha256', $token))
            ->where('is_admin', true)
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Acesso restrito ao administrador.'], 403);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
