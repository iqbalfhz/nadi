<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_products', function (Blueprint $table) {
            // Same unit as pricing_unit implies (grams for per_100g, pcs for
            // per_pcs). Null means unlimited/untracked — a vendor with an
            // easily-restocked item (bottled water, say) doesn't have to
            // pick an arbitrary number.
            $table->unsignedInteger('initial_stock')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_products', function (Blueprint $table) {
            $table->dropColumn('initial_stock');
        });
    }
};
