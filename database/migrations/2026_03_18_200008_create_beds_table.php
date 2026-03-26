<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->string('bed_number', 20)->unique();         // e.g. BED-101
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ward', 100)->nullable();            // e.g. General, ICU, Private
            $table->string('room_number', 20)->nullable();
            $table->enum('type', ['general', 'private', 'icu', 'emergency', 'maternity'])->default('general');
            $table->enum('status', ['available', 'occupied', 'maintenance', 'reserved'])->default('available');
            $table->decimal('charge_per_day', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
