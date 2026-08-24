<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Nullable despite always being set by Ticket's creating hook for
            // new rows — any tickets already sold before this column existed
            // have no value to backfill, and MySQL allows multiple NULLs in
            // a unique column without conflict.
            $table->string('transaction_number')->nullable()->unique()->after('event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('transaction_number');
        });
    }
};
