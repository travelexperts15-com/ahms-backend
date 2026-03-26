<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->enum('day_of_week', ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('max_patients')->default(20);
            $table->string('consultation_room', 100)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // A doctor can only have one schedule per day
            $table->unique(['doctor_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
