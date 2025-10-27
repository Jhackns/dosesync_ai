<?php

namespace App\Services;

use App\Models\Medication;
use App\Models\DoseLog;
use Carbon\Carbon;

class ScheduleService
{
    /**
     * Genera los logs de dosis para un medicamento basado en su frecuencia
     */
    public function generateDoseLogs(Medication $medication)
    {
        $logsToInsert = [];
        
        $startDate = Carbon::parse($medication->start_date);
        $endDate = Carbon::parse($medication->end_date);
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            switch ($medication->frequency_type) {
                case 'daily':
                    $logsToInsert[] = $this->buildLogArray($medication, $currentDate, '09:00:00');
                    break;
                    
                case 'twice_day':
                    $logsToInsert[] = $this->buildLogArray($medication, $currentDate, '09:00:00');
                    $logsToInsert[] = $this->buildLogArray($medication, $currentDate, '21:00:00');
                    break;
                    
                case 'every_8_hours':
                    $logsToInsert[] = $this->buildLogArray($medication, $currentDate, '08:00:00');
                    $logsToInsert[] = $this->buildLogArray($medication, $currentDate, '16:00:00');
                    $logsToInsert[] = $this->buildLogArray($medication, $currentDate, '23:59:00');
                    break;
            }
            
            $currentDate->addDay();
        }
        
        // Inserción masiva optimizada
        if (!empty($logsToInsert)) {
            DoseLog::insert($logsToInsert);
        }
    }

    /**
     * Construye el array de datos para insertar un log de dosis
     */
    private function buildLogArray(Medication $medication, Carbon $date, string $time): array
    {
        $scheduledAt = $date->copy()->setTimeFromTimeString($time);
        
        return [
            'medication_id' => $medication->id,
            'scheduled_at' => $scheduledAt->toDateTimeString(),
            'status' => 'scheduled',
            'created_at' => now(),
        ];
    }
}