<?php

namespace App\Services;

use App\Models\User;

class TRES_AgentService
{
    /**
     * Calcula el riesgo TRES para un usuario
     */
    public function calculateRisk(User $user)
    {
        $riskReport = [];

        // Optimización (Eager Loading): Carga todos los medicamentos del usuario con doseLogs y symptomLogs
        $medications = $user->medications()->with('doseLogs.symptomLogs')->get();

        foreach ($medications as $medication) {
            $totalDosesTaken = 0;
            $dosesWithSymptoms = 0;
            $symptomFrequency = [];

            foreach ($medication->doseLogs as $doseLog) {
                // Contar Dosis Tomadas
                if ($doseLog->status === 'on_time' || $doseLog->status === 'late') {
                    $totalDosesTaken++;
                }

                // Contar Síntomas
                if ($doseLog->symptomLogs->isNotEmpty()) {
                    $dosesWithSymptoms++;

                    // Frecuencia de Síntomas
                    foreach ($doseLog->symptomLogs as $symptom) {
                        if (isset($symptomFrequency[$symptom->symptom_name])) {
                            $symptomFrequency[$symptom->symptom_name]++;
                        } else {
                            $symptomFrequency[$symptom->symptom_name] = 1;
                        }
                    }
                }
            }

            // Calcular TRES
            $tresScore = ($totalDosesTaken > 0) ? ($dosesWithSymptoms / $totalDosesTaken) : 0;

            // Obtener Síntoma Común
            $commonSymptom = 'None';
            if (!empty($symptomFrequency)) {
                arsort($symptomFrequency);
                $commonSymptom = array_key_first($symptomFrequency);
            }

            // Construir Reporte
            $riskReport[] = [
                'medication_name' => $medication->name,
                'tres_score' => $tresScore,
                'common_symptom' => $commonSymptom,
                'total_doses_taken' => $totalDosesTaken,
            ];
        }

        return $riskReport;
    }
}
