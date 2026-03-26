<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('generic_name', 191)->nullable();
            $table->string('category', 100)->nullable();          // antibiotic, analgesic, etc.
            $table->string('type', 50)->nullable();               // tablet, syrup, injection, etc.
            $table->string('strength', 100)->nullable();          // 500mg, 250ml, etc.
            $table->string('manufacturer', 191)->nullable();
            $table->string('unit', 50)->default('tablet');        // tablet, ml, vial, etc.
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedSmallInteger('reorder_level')->default(10);
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->date('expiry_date')->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->enum('status', ['active', 'inactive', 'out_of_stock'])->default('active');
            $table->timestamps();
        });

        Schema::create('medicine_dispensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('medicine_name', 191);               // snapshot in case medicine is deleted
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispensed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_dispensations');
        Schema::dropIfExists('medicines');
    }
};
