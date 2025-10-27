<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('caregivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('caregiver_user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['patient_id', 'caregiver_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caregivers');
    }
};