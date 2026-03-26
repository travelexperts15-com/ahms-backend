<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->string('admission_number', 20)->unique();   // IPD-0001
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bed_id')->nullable()->constrained()->nullOnDelete();

            $table->date('admission_date');
            $table->time('admission_time');
            $table->date('discharge_date')->nullable();
            $table->time('discharge_time')->nullable();

            $table->string('admission_type', 50)->default('regular'); // regular, emergency, transfer
            $table->text('reason_for_admission')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_summary')->nullable();
            $table->text('discharge_notes')->nullable();
            $table->enum('discharge_condition', ['recovered', 'improved', 'unchanged', 'deteriorated', 'deceased'])->nullable();

            $table->enum('status', ['admitted', 'discharged', 'transferred'])->default('admitted');
            $table->foreignId('admitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('discharged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
