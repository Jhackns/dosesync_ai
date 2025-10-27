<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};