<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where the courier collects the document.
     *
     * The module shipped without it, and nobody noticed until a courier
     * actually ran the flow on a phone: they could see where the document was
     * going and not where to fetch it. The same hole exists on the web — this
     * was never an API omission, only the place it finally showed.
     *
     * Nullable so the column can land on a live table, but the request form
     * requires it: a delivery nobody can collect is not a request.
     */
    public function up(): void
    {
        Schema::table('messenger_deliveries', function (Blueprint $table) {
            $table->string('origin')->nullable()->after('sender_id');
        });
    }

    public function down(): void
    {
        Schema::table('messenger_deliveries', function (Blueprint $table) {
            $table->dropColumn('origin');
        });
    }
};
