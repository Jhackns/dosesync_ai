<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\TRES_AgentService;
use App\Services\GeminiAIService;

class ReportController extends Controller
{
    public function getTRESReport(Request $request, TRES_AgentService $agent, GeminiAIService $gemini)
    {
        $user = Auth::user();
        $results = $agent->calculateRisk($user);
        $prompt = 'Actúa como médico clínico. Lee el siguiente JSON del informe TRES. '
            . 'Redacta un resumen breve (3–4 frases) en español, con tono clínico, claro y neutro. '
            . 'Incluye: (1) el medicamento con mayor riesgo y su puntaje, '
            . '(2) los principales factores que contribuyen al riesgo (síntomas, adherencia, frecuencia), '
            . '(3) una recomendación puntual de seguimiento y signos de alarma. '
            . 'Evita repetir el JSON literal, no uses listas, usa oraciones fluidas. Datos: [DATOS_JSON]';
        $summary = $gemini->generateNarrative($prompt, json_encode($results));

        return response()->json([ 'data' => $results, 'summary' => $summary ]);
    }

    public function exportComplianceCSV(Request $request)
    {
        $user = Auth::user();
        $doseLogs = \App\Models\DoseLog::with(['medication', 'symptomLog'])
            ->whereHas('medication', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $fileName = 'compliance_report.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Dose ID', 'Medication Name', 'Scheduled At', 'Taken At', 'Status', 'Skip Reason (IA Classification)', 'Symptom Reported', 'Symptom Severity'];

        $callback = function() use($doseLogs, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($doseLogs as $log) {
                $row['Dose ID']  = $log->id;
                $row['Medication Name'] = $log->medication->name;
                $row['Scheduled At'] = $log->scheduled_at;
                $row['Taken At'] = $log->taken_at;
                $row['Status'] = $log->status;
                $row['Skip Reason (IA Classification)'] = $log->gemini_classification;
                $row['Symptom Reported'] = $log->symptomLog ? $log->symptomLog->symptom_name : 'N/A';
                $row['Symptom Severity'] = $log->symptomLog ? $log->symptomLog->severity : 'N/A';

                fputcsv($file, [$row['Dose ID'], $row['Medication Name'], $row['Scheduled At'], $row['Taken At'], $row['Status'], $row['Skip Reason (IA Classification)'], $row['Symptom Reported'], $row['Symptom Severity']]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}