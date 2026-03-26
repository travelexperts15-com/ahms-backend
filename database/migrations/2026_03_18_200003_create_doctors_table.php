<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('doctor_id', 20)->unique();    // e.g. DOC-0001
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('specialization', 191)->nullable();
            $table->string('qualification', 191)->nullable();
            $table->unsignedTinyInteger('experience')->default(0);  // years
            $table->string('phone', 30)->nullable();
            $table->string('email', 191)->nullable();
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->text('address')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('photo', 255)->nullable();
            $table->text('bio')->nullable();
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
