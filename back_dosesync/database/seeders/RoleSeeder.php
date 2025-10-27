<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        foreach (['Paciente', 'Cuidador', 'Admin'] as $name) {
            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                ['created_at' => $now]
            );
        }
    }
}
