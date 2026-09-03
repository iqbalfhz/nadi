<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the worker says they did the job, as distinct from when the server
     * heard about it.
     *
     * A report filed at 09:14 in a basement and flushed at 11:03 when signal
     * returns would otherwise carry created_at 11:03 — and a supervisor
     * reading "twelve reports, all at 11:03" learns nothing from the times.
     *
     * created_at keeps its meaning (when this arrived); submitted_at carries
     * the claim. Neither one lies, which is why this is a second column and
     * not an override of the first. Null for anything filed from the web,
     * where the two are the same moment.
     */
    public function up(): void
    {
        Schema::table('ob_checklists', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('ob_checklists', function (Blueprint $table) {
            $table->dropColumn('submitted_at');
        });
    }
};
