<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opd_visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_number', 20)->unique();       // OPD-0001
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->date('visit_date');
            $table->time('visit_time');

            // Clinical details
            $table->string('chief_complaint', 500)->nullable();
            $table->text('history_of_illness')->nullable();
            $table->text('examination_findings')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('notes')->nullable();

            // Vitals
            $table->string('blood_pressure', 20)->nullable();   // e.g. 120/80
            $table->decimal('temperature', 4, 1)->nullable();   // °C
            $table->unsignedSmallInteger('pulse_rate')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->decimal('weight', 5, 2)->nullable();        // kg
            $table->decimal('height', 5, 2)->nullable();        // cm
            $table->unsignedTinyInteger('oxygen_saturation')->nullable(); // %

            $table->enum('status', ['in_progress', 'completed', 'referred'])->default('in_progress');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opd_visits');
    }
};
