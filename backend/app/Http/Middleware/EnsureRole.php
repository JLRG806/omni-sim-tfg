<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de autorización por rol.
 * Verifica que el usuario autenticado tenga uno de los roles requeridos.
 * Se registra con el alias 'role' en bootstrap/app.php.
 */
class EnsureRole
{
    /**
     * Comprueba que el rol del usuario esté entre los roles permitidos.
     * Devuelve 403 con mensaje en español si no tiene permisos.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Roles permitidos (ej: 'admin', 'profesor')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! in_array($request->user()?->rol, $roles)) {
            return response()->json([
                'message' => 'No tiene permisos para esta acción',
            ], 403);
        }

        return $next($request);
    }
}
