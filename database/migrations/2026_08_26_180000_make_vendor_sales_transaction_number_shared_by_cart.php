<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A cart checkout writes one VendorSale row per line item, all sharing
     * one transaction_number — so it can no longer be unique per row.
     */
    public function up(): void
    {
        Schema::table('vendor_sales', function (Blueprint $table) {
            $table->dropUnique(['transaction_number']);
            $table->index('transaction_number');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_sales', function (Blueprint $table) {
            $table->dropIndex(['transaction_number']);
            $table->unique('transaction_number');
        });
    }
};
