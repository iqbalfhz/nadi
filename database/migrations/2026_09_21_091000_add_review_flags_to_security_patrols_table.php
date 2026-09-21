<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Why a patrol is worth a second look — never why it was refused.
 *
 * A single scan always looks legitimate. What gives a fabricated round away
 * is the pattern across scans, and the server is the only place that has all
 * of them: every guard, every post, every time.
 *
 * Nothing here blocks anything, and that is deliberate rather than timid.
 * Every signal computed into this column has an honest explanation — a shift
 * swapped, a post moved, two guards sharing a handset, an outbox coming home
 * all at once. Refusing on them would punish the guard working in the
 * basement, who is the one with the worst signal and the best attendance.
 *
 * Stored rather than computed on read for two reasons: reports arrive late
 * and out of order, so a scan's neighbours can appear hours after it did; and
 * the interval arithmetic differs between MySQL and SQLite, which would mean
 * the production branch of the query was the one branch never under test.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_patrols', function (Blueprint $table): void {
            $table->json('review_flags')->nullable()->after('submitted_at_claimed');
            $table->timestamp('reviewed_at')->nullable()->after('review_flags');
        });
    }

    public function down(): void
    {
        Schema::table('security_patrols', function (Blueprint $table): void {
            $table->dropColumn(['review_flags', 'reviewed_at']);
        });
    }
};
