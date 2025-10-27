<?php

namespace Database\Factories;

use App\Models\Medication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medication>
 */
class MedicationFactory extends Factory
{
    protected $model = Medication::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-30 days', 'now');
        $end = (clone $start)->modify('+'.fake()->numberBetween(7, 60).' days');

        return [
            'user_id' => User::factory()->paciente(),
            'name' => fake()->randomElement(['Amoxicilina', 'Ibuprofeno', 'Paracetamol', 'Metformina', 'Lisinopril']),
            'dosage_text' => fake()->randomElement(['500mg', '10mg', '1 tableta', '2 cápsulas']),
            'frequency_type' => fake()->randomElement(['diaria', 'cada 8 horas', 'semanal']),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'interaction_rule' => fake()->optional(0.5)->sentence(),
            'created_at' => now(),
        ];
    }
}