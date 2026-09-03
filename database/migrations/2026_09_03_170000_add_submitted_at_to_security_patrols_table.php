<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the guard says they reached the checkpoint, as distinct from when
     * the server heard about it. See App\Support\FieldReportTime.
     *
     * This matters more here than anywhere else: a patrol round's whole value
     * is the times, and stairwells and parking decks are exactly where signal
     * is not.
     */
    public function up(): void
    {
        Schema::table('security_patrols', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('incident_report');
        });
    }

    public function down(): void
    {
        Schema::table('security_patrols', function (Blueprint $table) {
            $table->dropColumn('submitted_at');
        });
    }
};
