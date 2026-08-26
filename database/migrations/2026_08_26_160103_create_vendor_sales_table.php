<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bazaar_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_product_id')->constrained()->restrictOnDelete();
            $table->string('transaction_number')->nullable()->unique();
            $table->unsignedInteger('quantity');
            $table->string('pricing_unit');
            $table->unsignedInteger('price');
            $table->string('payment_method');
            $table->foreignId('sold_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['bazaar_id', 'created_at']);
            $table->index(['vendor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_sales');
    }
};
