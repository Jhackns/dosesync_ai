<?php

namespace App\Http\Controllers;

use App\Models\Caregiver;
use App\Models\User;
use App\Models\Role;
use App\Services\TRES_AgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaregiverController extends Controller
{
    // Prompt 24: Invitar cuidador
    public function inviteCaregiver(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Buscar dinámicamente el rol "Cuidador" para evitar asumir ID fijo
        $caregiverRoleId = Role::where('name', 'Cuidador')->value('id');
        $caregiverUser = User::where('email', $data['email'])
            ->where('role_id', $caregiverRoleId)
            ->firstOrFail();

        $patientUser = Auth::user();

        $relation = Caregiver::firstOrCreate(
            [
                'patient_id' => $patientUser->id,
                'caregiver_user_id' => $caregiverUser->id,
            ],
            [ 'created_at' => now() ]
        );

        $status = $relation->wasRecentlyCreated ? 201 : 200;
        return response()->json([
            'message' => $relation->wasRecentlyCreated ? 'Cuidador invitado correctamente.' : 'La relación ya existía.',
            'data' => $relation,
        ], $status);
    }

    // Prompt 25: Reporte del paciente para cuidador
    public function getPatientReport(User $patient, TRES_AgentService $tresService)
    {
        $caregiverUser = Auth::user();

        $authorized = Caregiver::where('patient_id', $patient->id)
            ->where('caregiver_user_id', $caregiverUser->id)
            ->exists();

        if (!$authorized) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $report = $tresService->calculateRisk($patient);
        return response()->json(['report' => $report]);
    }
}
