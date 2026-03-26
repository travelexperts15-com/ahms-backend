<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master list of available lab tests
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191)->unique();
            $table->string('code', 30)->unique()->nullable();     // e.g. CBC, LFT, RFT
            $table->string('category', 100)->nullable();          // Hematology, Biochemistry, etc.
            $table->text('description')->nullable();
            $table->string('sample_type', 100)->nullable();       // blood, urine, stool, etc.
            $table->string('normal_range', 191)->nullable();
            $table->string('unit', 50)->nullable();               // mg/dL, mmol/L, etc.
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Lab orders (ordered for a patient)
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();         // LAB-0001
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opd_visit_id')->nullable()->constrained('opd_visits')->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained()->nullOnDelete();
            $table->date('ordered_date');
            $table->text('clinical_notes')->nullable();
            $table->enum('status', ['pending', 'sample_collected', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Individual test results within an order
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('result_value', 255)->nullable();
            $table->string('unit', 50)->nullable();
            $table->string('normal_range', 191)->nullable();
            $table->enum('result_flag', ['normal', 'low', 'high', 'critical'])->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resulted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('lab_tests');
    }
};
