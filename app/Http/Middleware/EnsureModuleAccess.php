<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user || ! $user->active) {
            abort(403, 'Seu acesso ao sistema está desativado.');
        }

        if (! $user->canAccess($module)) {
            abort(403, 'Você não possui permissão para acessar este módulo.');
        }

        return $next($request);
    }
}
