<?php

namespace Database\Factories;

use App\Models\DoseLog;
use App\Models\Medication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoseLog>
 */
class DoseLogFactory extends Factory
{
    protected $model = DoseLog::class;

    public function definition(): array
    {
        // Simular dosis futuras programadas
        $scheduled = fake()->dateTimeBetween('+1 hours', '+7 days');

        return [
            'medication_id' => Medication::factory(),
            'scheduled_at' => $scheduled->format('Y-m-d H:i:s'),
            'taken_at' => null,
            'status' => 'scheduled',
            'skip_reason' => null,
            'gemini_classification' => fake()->optional(0.5)->randomElement(['adherente', 'no adherente', 'parcial']),
            'created_at' => now(),
        ];
    }
}
