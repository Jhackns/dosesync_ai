<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dose_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained('medications')->cascadeOnDelete()->cascadeOnUpdate();
            $table->dateTime('scheduled_at');
            $table->dateTime('taken_at')->nullable();
            $table->string('status');
            $table->string('skip_reason')->nullable();
            $table->string('gemini_classification')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dose_logs');
    }
};