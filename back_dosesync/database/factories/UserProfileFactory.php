<?php

namespace Database\Factories;

use App\Models\UserProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'phone_number' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'updated_at' => now(),
        ];
    }
}