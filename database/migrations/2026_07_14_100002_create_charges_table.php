<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // e.g. "VAT (7.5%)", "Service Charge"
            $table->string('type');                       // percent | fixed
            $table->decimal('rate', 12, 2);               // 7.5 (%) or 200 (₦)
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete(); // null = every section
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
