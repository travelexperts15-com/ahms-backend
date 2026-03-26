<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('dob')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name', 191)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->date('joining_date')->nullable();
            $table->string('position', 191)->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->string('bank_account', 100)->nullable();
            $table->string('national_id', 100)->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
