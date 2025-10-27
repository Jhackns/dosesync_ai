<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PermissionsController extends Controller
{
    public function index()
    {
        return response()->json($this->permissionMap());
    }

    public function show($id)
    {
        $map = $this->permissionMap();
        if (!isset($map[$id])) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }
        return response()->json([$id => $map[$id]]);
    }

    private function permissionMap(): array
    {
        // Mapa estático de permisos por rol (endpoints permitidos)
        return [
            'Admin' => [
                // Gestión de usuarios
                'GET /api/users',
                'POST /api/users',
                'GET /api/users/{id}',
                'PATCH /api/users/{id}',
                'DELETE /api/users/{id}',
                // Gestión de roles
                'GET /api/roles',
                'POST /api/roles',
                'GET /api/roles/{id}',
                'PATCH /api/roles/{id}',
                // Listado de permisos
                'GET /api/permissions/rol',
                'GET /api/permissions/rol/{id}',
            ],
            'Paciente' => [
                'GET /api/medications',
                'POST /api/medications',
                'GET /api/medications/{id}',
                'PATCH /api/medications/{id}',
                'DELETE /api/medications/{id}',
                'GET /api/dose_logs',
                'POST /api/log_dose',
                'GET /api/report/tres',
                'POST /api/caregiver/invite',
            ],
            'Cuidador' => [
                'GET /api/caregiver/report/{patient}',
            ],
        ];
    }
}