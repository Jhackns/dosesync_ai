<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ScheduleService;

class MedicationController extends Controller
{
    public function index()
    {
        $medications = Medication::where('user_id', Auth::id())->get();
        return response()->json($medications);
    }

    public function store(Request $request, ScheduleService $scheduleService)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'dosage_text' => ['required', 'string', 'max:255'],
            'frequency_type' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'interaction_rule' => ['nullable', 'string'],
        ]);

        $medication = Medication::create(array_merge($data, [
            'user_id' => Auth::id(),
            'created_at' => now(),
        ]));

        // Genera el calendario de dosis automáticamente
        $scheduleService->generateDoseLogs($medication);

        return response()->json($medication, 201);
    }

    public function show(string $id)
    {
        $medication = Medication::findOrFail($id);
        if ($medication->user_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        return response()->json($medication);
    }

    public function update(Request $request, string $id)
    {
        $medication = Medication::findOrFail($id);
        if ($medication->user_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'dosage_text' => ['sometimes', 'string', 'max:255'],
            'frequency_type' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'interaction_rule' => ['nullable', 'string'],
        ]);

        $medication->update($data);
        return response()->json($medication);
    }

    public function destroy(string $id)
    {
        $medication = Medication::findOrFail($id);
        if ($medication->user_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $medication->delete();
        return response()->json(['message' => 'Eliminado']);
    }
}