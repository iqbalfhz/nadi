<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PB1 for the bazaar, charged on top of the listed price.
 *
 * The rate lives on the kios, not the bazaar: vendors are independent
 * businesses and not all of them are liable — one under the turnover
 * threshold sits at 0 while the stall beside it charges 10.
 *
 * vendor_sales.price keeps its existing meaning — the pre-tax subtotal,
 * which is also the vendor's own share — so every sale recorded before
 * today stays correct with a tax of zero, and the settlement report needs
 * no reinterpretation. What the customer paid is price + tax_amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('name');
        });

        Schema::table('vendor_sales', function (Blueprint $table): void {
            // Both snapshotted at the point of sale, like price and
            // pricing_unit already are: changing a kios's rate afterwards
            // must never rewrite what a customer was actually charged.
            $table->decimal('tax_rate', 5, 2)->default(0)->after('price');
            $table->unsignedInteger('tax_amount')->default(0)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropColumn('tax_rate');
        });

        Schema::table('vendor_sales', function (Blueprint $table): void {
            $table->dropColumn(['tax_rate', 'tax_amount']);
        });
    }
};
