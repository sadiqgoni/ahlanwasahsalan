<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->unique();
            $table->foreignId('shift_id')->constrained();
            $table->foreignId('user_id')->constrained(); // cashier who recorded it
            $table->decimal('total', 12, 2);
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->decimal('change_due', 12, 2)->default(0);
            $table->string('payment_method');            // cash | transfer | pos
            $table->string('payment_reference')->nullable();
            $table->string('status')->default('completed'); // completed | voided
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users');
            $table->string('void_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
