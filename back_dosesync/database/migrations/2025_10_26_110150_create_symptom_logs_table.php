<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('symptom_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dose_log_id')->constrained('dose_logs')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('symptom_name');
            $table->unsignedTinyInteger('severity');
            $table->dateTime('reported_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('symptom_logs');
    }
};