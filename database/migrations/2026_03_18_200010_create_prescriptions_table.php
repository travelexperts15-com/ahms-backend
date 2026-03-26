<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_number', 20)->unique();   // RX-0001
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opd_visit_id')->nullable()->constrained('opd_visits')->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->date('prescribed_date');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'dispensed', 'cancelled'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->string('medicine_name', 191);
            $table->string('dosage', 100)->nullable();           // e.g. 500mg
            $table->string('frequency', 100)->nullable();        // e.g. 3x daily
            $table->string('duration', 100)->nullable();         // e.g. 7 days
            $table->string('route', 50)->nullable();             // oral, IV, IM, topical
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
    }
};
