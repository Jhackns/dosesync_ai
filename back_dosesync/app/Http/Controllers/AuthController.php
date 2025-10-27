<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $patientRoleId = DB::table('roles')->where('name', 'Paciente')->value('id');
        if (!$patientRoleId) {
            $patientRoleId = DB::table('roles')->insertGetId(['name' => 'Paciente', 'created_at' => now()]);
        }

        $user = User::create([
            'name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $patientRoleId,
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'full_name' => $data['full_name'],
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'address' => $data['address'] ?? null,
            'updated_at' => now(),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Registro exitoso. ¡Bienvenido/a al sistema!',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    // Login action
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $response = [
            'message' => 'Login exitoso.',
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'role_id']),
        ];

        // Instrucciones para Admin
        if ($user->role && $user->role->name === 'Admin') {
            $response['admin_instructions'] = [
                'overview' => 'Como administrador, puedes gestionar usuarios, roles y permisos.',
                'headers' => [
                    'Authorization' => 'Bearer <token>',
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'routes' => [
                    'users' => [
                        'list' => ['method' => 'GET', 'url' => '/api/users'],
                        'create' => ['method' => 'POST', 'url' => '/api/users'],
                        'show' => ['method' => 'GET', 'url' => '/api/users/{id}'],
                        'edit' => ['method' => 'PATCH', 'url' => '/api/users/{id}'],
                        'delete' => ['method' => 'DELETE', 'url' => '/api/users/{id}'],
                    ],
                    'roles' => [
                        'list' => ['method' => 'GET', 'url' => '/api/roles'],
                        'create' => ['method' => 'POST', 'url' => '/api/roles'],
                        'show' => ['method' => 'GET', 'url' => '/api/roles/{id}'],
                        'edit' => ['method' => 'PATCH', 'url' => '/api/roles/{id}'],
                    ],
                    'permissions' => [
                        'list_all' => ['method' => 'GET', 'url' => '/api/permissions'],
                        'list_by_role' => ['method' => 'GET', 'url' => '/api/permissions/{roleId}'],
                    ],
                ],
                'examples' => [
                    'create_user_body' => [
                        'full_name' => 'Pedro Sanchez',
                        'email' => 'pedro@example.com',
                        'password' => '12345678',
                        'date_of_birth' => '1991-01-01',
                        'phone_number' => '999999999',
                        'address' => 'Av. Siempre Viva 123',
                        'role_id' => 2,
                    ],
                    'edit_user_body' => [
                        'name' => 'Nombre Actualizado',
                        'email' => 'nuevo@example.com',
                        'password' => 'nuevaclave',
                        'role' => 'Cuidador',
                    ],
                    'create_role_body' => [
                        'name' => 'Soporte',
                    ],
                ],
            ];
        }

        return response()->json($response);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('userProfile');
        return response()->json([
            'message' => 'Información del usuario obtenida correctamente.',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }
        return response()->json(['message' => 'Has salido del sistema. ¡Hasta pronto!']);
    }
}