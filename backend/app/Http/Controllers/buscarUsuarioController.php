<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CU-08 Buscar Usuario (superset de CU-04 Listar Usuarios)
 *
 * Sin ?q=  → devuelve todos los usuarios (comportamiento CU-04)
 * Con ?q=  → filtra por nombre, correo o rol (comportamiento CU-08)
 *
 * Actor: admin
 * Route: GET /api/v1/usuarios[?q=término]  (auth:sanctum, role:admin)
 */
class buscarUsuarioController extends Controller
{
    /**
     * Lista usuarios, con filtro opcional por nombre, correo o rol.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->query('q');

        $usuarios = User::orderBy('name')
            ->when($q, function ($query, $termino) {
                $query->where(function ($q) use ($termino) {
                    $q->where('name',  'ilike', "%{$termino}%")
                      ->orWhere('email', 'ilike', "%{$termino}%")
                      ->orWhere('rol',   'ilike', "%{$termino}%");
                });
            })
            ->get(['id', 'name', 'email', 'rol', 'estado']);

        return response()->json(['data' => $usuarios]);
    }
}
