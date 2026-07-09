<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // Snapshots — receipts must stay accurate even if menu changes later
            $table->string('product_name');
            $table->string('section');                 // category name at time of sale
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);      // base + options, per unit
            $table->decimal('line_total', 12, 2);
            $table->json('options')->nullable();       // [{name, price}, ...]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
