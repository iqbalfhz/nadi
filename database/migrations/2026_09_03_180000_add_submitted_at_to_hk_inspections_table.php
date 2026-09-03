<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the supervisor says they inspected the point, as distinct from
     * when the server heard about it. See App\Support\FieldReportTime.
     *
     * The job that pushes HK reports to Telegram already documents why this
     * matters: a supervisor standing in a toilet on mall wifi must never wait
     * on the network, which means the report is often written well before it
     * is sent.
     */
    public function up(): void
    {
        Schema::table('hk_inspections', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('follow_up');
        });
    }

    public function down(): void
    {
        Schema::table('hk_inspections', function (Blueprint $table) {
            $table->dropColumn('submitted_at');
        });
    }
};
