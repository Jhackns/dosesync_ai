<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolesController extends Controller
{
    // Listar roles (solo Admin)
    public function index()
    {
        $roles = Role::orderBy('id', 'asc')->get(['id', 'name']);
        return response()->json([
            'message' => 'Listado de roles obtenido correctamente.',
            'count' => $roles->count(),
            'data' => $roles,
        ]);
    }

    // Crear rol (solo Admin)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        $role = Role::create(['name' => $validated['name']]);

        return response()->json([
            'message' => 'Rol creado exitosamente.',
            'data' => $role->only(['id', 'name']),
        ], 201);
    }

    // Mostrar rol por ID (solo Admin)
    public function show($id)
    {
        $role = Role::findOrFail($id);
        return response()->json([
            'message' => 'Rol obtenido correctamente.',
            'data' => $role->only(['id', 'name']),
        ]);
    }

    // Actualizar rol (solo Admin)
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
        ]);

        $role->name = $validated['name'];
        $role->save();

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'data' => $role->only(['id', 'name']),
        ]);
    }
}