<?php

namespace Database\Factories;

use App\Models\SymptomLog;
use App\Models\DoseLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SymptomLog>
 */
class SymptomLogFactory extends Factory
{
    protected $model = SymptomLog::class;

    public function definition(): array
    {
        return [
            'dose_log_id' => DoseLog::factory(),
            'symptom_name' => fake()->randomElement(['náusea', 'dolor de cabeza', 'mareo', 'fatiga', 'insomnio']),
            'severity' => fake()->numberBetween(1, 5),
            'reported_at' => fake()->dateTimeBetween('-3 days', 'now')->format('Y-m-d H:i:s'),
        ];
    }
}