<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Medication;
use App\Models\DoseLog;
use App\Models\SymptomLog;
use App\Services\ScheduleService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // Obtener IDs de roles
        $rolePaciente = DB::table('roles')->where('name', 'Paciente')->value('id');
        $roleCuidador = DB::table('roles')->where('name', 'Cuidador')->value('id');
        $roleAdmin    = DB::table('roles')->where('name', 'Admin')->value('id');

        // Crear usuarios por defecto (uno por rol)
        $admin = User::updateOrCreate(
            ['email' => 'administrador@example.com'],
            [
                'name' => 'Administrador del Sistema',
                'password' => Hash::make('password123'),
                'role_id' => $roleAdmin,
            ]
        );
        UserProfile::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'full_name' => 'Administrador del Sistema',
                'updated_at' => now(),
            ]
        );

        $caregiver = User::updateOrCreate(
            ['email' => 'cuidador@example.com'],
            [
                'name' => 'Usuario Cuidador',
                'password' => Hash::make('password123'),
                'role_id' => $roleCuidador,
            ]
        );
        UserProfile::updateOrCreate(
            ['user_id' => $caregiver->id],
            [
                'full_name' => 'Usuario Cuidador',
                'updated_at' => now(),
            ]
        );

        $patient = User::updateOrCreate(
            ['email' => 'paciente@example.com'],
            [
                'name' => 'Usuario Paciente',
                'password' => Hash::make('password123'),
                'role_id' => $rolePaciente,
            ]
        );
        UserProfile::updateOrCreate(
            ['user_id' => $patient->id],
            [
                'full_name' => 'Usuario Paciente',
                'updated_at' => now(),
            ]
        );

        // Sembrar medicamentos y logs para el paciente por defecto
        $schedule = new ScheduleService();

        $med1 = Medication::updateOrCreate(
            ['user_id' => $patient->id, 'name' => 'Paracetamol'],
            [
                'dosage_text' => '500mg',
                'frequency_type' => 'twice_day',
                'start_date' => now()->subDays(5)->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
                'interaction_rule' => 'Evitar alcohol',
                'created_at' => now(),
            ]
        );
        $schedule->generateDoseLogs($med1);

        $med2 = Medication::updateOrCreate(
            ['user_id' => $patient->id, 'name' => 'Amoxicilina'],
            [
                'dosage_text' => '500mg',
                'frequency_type' => 'every_8_hours',
                'start_date' => now()->subDays(3)->toDateString(),
                'end_date' => now()->addDays(7)->toDateString(),
                'interaction_rule' => 'Tomar con comida',
                'created_at' => now(),
            ]
        );
        $schedule->generateDoseLogs($med2);

        // Marcar algunas dosis como tomadas y crear síntomas asociados
        $doseLogs1 = DoseLog::where('medication_id', $med1->id)->orderBy('scheduled_at')->take(3)->get();
        foreach ($doseLogs1 as $i => $dl) {
            $dl->status = $i < 2 ? 'on_time' : 'late';
            $dl->taken_at = $dl->scheduled_at; // on_time
            if ($dl->status === 'late') {
                $dl->taken_at = \Carbon\Carbon::parse($dl->scheduled_at)->addMinutes(30)->toDateTimeString();
            }
            $dl->save();

            // Crear síntomas en dos de las tres dosis
            if ($i < 2) {
                SymptomLog::create([
                    'dose_log_id' => $dl->id,
                    'symptom_name' => $i === 0 ? 'dolor de cabeza' : 'náusea',
                    'severity' => $i === 0 ? 2 : 3,
                    'reported_at' => now(),
                ]);
            }
        }

        $doseLogs2 = DoseLog::where('medication_id', $med2->id)->orderBy('scheduled_at')->take(2)->get();
        foreach ($doseLogs2 as $i => $dl) {
            $dl->status = $i === 0 ? 'on_time' : 'skipped';
            $dl->taken_at = $i === 0 ? $dl->scheduled_at : null;
            $dl->skip_reason = $i === 1 ? 'Olvido' : null;
            $dl->save();

            if ($i === 0) {
                SymptomLog::create([
                    'dose_log_id' => $dl->id,
                    'symptom_name' => 'fatiga',
                    'severity' => 1,
                    'reported_at' => now(),
                ]);
            }
        }

        $this->command->info(' Base de datos poblada:');
        $this->command->info('   • Roles (Paciente, Cuidador, Admin)');
        $this->command->info('   • Usuarios por defecto creados:');
        $this->command->info('     - Admin: administrador@example.com / password123');
        $this->command->info('     - Cuidador: cuidador@example.com / password123');
        $this->command->info('     - Paciente: paciente@example.com / password123');
        $this->command->info('   • Medicamentos y dose logs creados para paciente@example.com');
    }
}
