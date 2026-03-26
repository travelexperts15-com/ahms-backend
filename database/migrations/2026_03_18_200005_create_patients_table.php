<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_id', 20)->unique();        // PAT-0001
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('dob');
            $table->string('blood_group', 10)->nullable();     // A+, O-, B+, etc.
            $table->string('phone', 30)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('address')->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_disease')->nullable();
            $table->string('emergency_contact_name', 191)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->date('registration_date');
            $table->string('photo', 255)->nullable();
            $table->enum('status', ['active', 'inactive', 'deceased'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
