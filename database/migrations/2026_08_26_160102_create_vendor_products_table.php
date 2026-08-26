<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('pricing_unit');
            $table->unsignedInteger('price');
            $table->timestamps();

            $table->index(['vendor_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_products');
    }
};
