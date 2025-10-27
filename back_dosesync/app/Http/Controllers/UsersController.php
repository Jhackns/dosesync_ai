<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    // Listar todos los usuarios (solo Admin)
    public function index()
    {
        $users = User::with('role')
            ->orderBy('id', 'asc')
            ->get(['id', 'name', 'email', 'role_id', 'created_at']);

        return response()->json([
            'message' => 'Listado de usuarios obtenido correctamente.',
            'count' => $users->count(),
            'data' => $users,
        ]);
    }

    // Crear usuario (solo Admin)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', Rule::in(['Paciente', 'Cuidador', 'Admin'])],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        // Determinar rol
        $roleId = $validated['role_id'] ?? null;
        if (!$roleId && isset($validated['role'])) {
            $roleId = DB::table('roles')->where('name', $validated['role'])->value('id');
        }
        if (!$roleId) {
            $roleId = DB::table('roles')->where('name', 'Paciente')->value('id');
        }

        $user = User::create([
            'name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $roleId,
        ]);

        // Crear perfil asociado con datos completos
        UserProfile::create([
            'user_id' => $user->id,
            'full_name' => $validated['full_name'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente.',
            'data' => $user->only(['id', 'name', 'email', 'role_id']),
        ], 201);
    }

    // Mostrar usuario por ID (solo Admin)
    public function show($id)
    {
        $user = User::with('role')->findOrFail($id);
        return response()->json([
            'message' => 'Usuario obtenido correctamente.',
            'data' => $user->only(['id', 'name', 'email', 'role_id']),
        ]);
    }

    // Actualizar usuario (solo Admin)
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Normalizar payload: permitir role como objeto {id, name}
        $payload = $request->all();
        if (isset($payload['role']) && is_array($payload['role'])) {
            if (isset($payload['role']['id'])) {
                $payload['role_id'] = $payload['role']['id'];
            } elseif (isset($payload['role']['name'])) {
                $payload['role'] = $payload['role']['name'];
            }
            unset($payload['role']);
        }
        $request->replace($payload);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:6'],
            'role' => ['nullable', 'string', Rule::in(['Paciente', 'Cuidador', 'Admin'])],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }
        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }
        if (array_key_exists('password', $validated)) {
            $user->password = Hash::make($validated['password']);
        }
        // Actualizar rol si se proporciona
        $roleId = $validated['role_id'] ?? null;
        if (!$roleId && isset($validated['role'])) {
            $roleId = DB::table('roles')->where('name', $validated['role'])->value('id');
        }
        if ($roleId) {
            $user->role_id = $roleId;
        }

        $user->save();

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data' => $user->only(['id', 'name', 'email', 'role_id']),
        ]);
    }

    // Eliminar usuario (solo Admin)
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
