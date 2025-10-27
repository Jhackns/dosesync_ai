<?php

namespace App\Http\Controllers;

use App\Models\DoseLog;
use App\Models\SymptomLog;
use App\Services\GeminiAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DoseLogController extends Controller
{
    public function index(Request $request)
    {
        $medicationId = $request->query('medication_id');
        $query = DoseLog::with('medication')
            ->whereHas('medication', function ($q) {
                $q->where('user_id', Auth::id());
            });
        if ($medicationId) {
            $query->where('medication_id', $medicationId);
        }
        $logs = $query->orderBy('scheduled_at')->get(['id','medication_id','scheduled_at','taken_at','status','skip_reason']);
        return response()->json(['count' => $logs->count(), 'data' => $logs]);
    }

    public function logDose(Request $request, GeminiAIService $geminiService)
    {
        $data = $request->validate([
            'dose_log_id' => ['required', 'integer', 'exists:dose_logs,id'],
            'status' => ['required', 'string', Rule::in(['on_time', 'late', 'skipped'])],
            'taken_at' => ['nullable', 'date'],
            'skip_reason' => ['nullable', 'string'],
            'symptom_name' => ['nullable', 'string'],
            'severity' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $doseLog = DoseLog::with('medication')->findOrFail($data['dose_log_id']);
        if (!$doseLog->medication || $doseLog->medication->user_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $doseLog->status = $data['status'];
        $doseLog->taken_at = $data['taken_at'] ?? $doseLog->taken_at;
        $doseLog->skip_reason = $data['skip_reason'] ?? null;
        // Clasificación con Gemini si hay motivo de omisión
        if (!empty($doseLog->skip_reason)) {
            $prompt = 'Actúa como médico clínico y clasifica el motivo de omisión de dosis en UNA categoría EXACTA entre: Olvido | Efecto Secundario | Viaje | Ocupado | Costo | Otro. '
                . 'Devuelve solo la categoría (una palabra), sin explicación adicional. '
                . 'Considera sinónimos y lenguaje coloquial (p. ej., "se me pasó", "mareado", "fuera de casa", "no tuve tiempo", "muy caro").';
            $classification = $geminiService->classifyText($prompt, $doseLog->skip_reason);
            $doseLog->gemini_classification = $classification;
        }
        $doseLog->save();

        if (!empty($data['symptom_name']) && !empty($data['severity'])) {
            SymptomLog::create([
                'dose_log_id' => $doseLog->id,
                'symptom_name' => $data['symptom_name'],
                'severity' => $data['severity'],
                'reported_at' => now(),
            ]);
        }

        $optimization_suggestion = null;
        if (in_array($doseLog->status, ['on_time', 'late'])) {
            $takenAt = new \DateTime($doseLog->taken_at);
            $scheduledAt = new \DateTime($doseLog->scheduled_at);
            $diff = $takenAt->getTimestamp() - $scheduledAt->getTimestamp();
            $delayMinutes = round($diff / 60);

            if ($delayMinutes > 10 && $delayMinutes < 20) { // Example consistency window
                $recentDoses = DoseLog::where('medication_id', $doseLog->medication_id)
                    ->where('status', 'late')
                    ->where('id', '!=', $doseLog->id)
                    ->orderBy('scheduled_at', 'desc')
                    ->take(10) // Check last 10 late doses
                    ->get();

                $consistentLateDoses = 0;
                foreach ($recentDoses as $recentDose) {
                    $recentTakenAt = new \DateTime($recentDose->taken_at);
                    $recentScheduledAt = new \DateTime($recentDose->scheduled_at);
                    $recentDiff = $recentTakenAt->getTimestamp() - $recentScheduledAt->getTimestamp();
                    $recentDelayMinutes = round($recentDiff / 60);
                    if ($recentDelayMinutes > 10 && $recentDelayMinutes < 20) {
                        $consistentLateDoses++;
                    }
                }

                if ($consistentLateDoses >= 5) {
                    $optimization_suggestion = "Hemos notado que consistentemente toma esta dosis con un retraso de aproximadamente {$delayMinutes} minutos. Considere mover la hora programada para mejorar la adherencia.";
                }
            }
        }

        return response()->json([
            'message' => 'Registro actualizado', 
            'dose_log' => $doseLog,
            'optimization_suggestion' => $optimization_suggestion
        ]);
    }

    public function getScheduledDoses(Request $request)
    {
        $today = now()->startOfDay();

        $scheduledDoses = DoseLog::with('medication')
            ->where('status', 'scheduled')
            ->whereHas('medication', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->whereDate('scheduled_at', $today->toDateString())
            ->orderBy('scheduled_at', 'asc')
            ->get();

        return response()->json($scheduledDoses);
    }
}
