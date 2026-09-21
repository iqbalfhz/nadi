<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the phone actually claimed, when we did not believe it.
 *
 * FieldReportTime::clamp() pulls an impossible submitted_at back into range
 * rather than refusing the report — refusing would mean a wrong clock loses
 * the report, which defeats the whole reason for filing offline. That is
 * still right. What was wrong is that it did so *silently*: a handset whose
 * clock runs three hours fast had its claim quietly replaced with "now", and
 * the row came out looking perfectly ordinary.
 *
 * So the one signal worth having was the one signal being destroyed. A
 * submitted_at in the future has no honest explanation — there is no shift
 * pattern, no basement, no outbox delay that produces it — which makes it the
 * only clock check here with no false positives.
 *
 * Null means the claim was accepted as sent, which is the ordinary case.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = ['ob_checklists', 'security_patrols', 'hk_inspections'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->timestamp('submitted_at_claimed')->nullable()->after('submitted_at');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('submitted_at_claimed');
            });
        }
    }
};
