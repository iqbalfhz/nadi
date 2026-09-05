<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where the mobile app's failures land.
 *
 * The APK is handed to officers directly, not through Play Store, so there is
 * no built-in crash reporting of any kind. Without this table a crash on a
 * guard's phone at 3am is known only to that phone: the app catches it, writes
 * it to the device log, and nobody ever sees it. The only route a field bug
 * had to the developer was an officer phoning up and remembering what they
 * had been doing.
 *
 * Sending to our own backend rather than Sentry or Crashlytics is deliberate:
 * this app carries evidence photos and officer tokens, and piping stack traces
 * to a third party is a decision of its own, not something to slip in quietly.
 *
 * Rows are grouped, not appended. A screen that fails on every tap can send
 * dozens of identical reports in seconds, and a list of two hundred copies of
 * one bug hides the other three.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_crash_reports', function (Blueprint $table) {
            $table->id();

            // sha256 of the message plus the top of the stack. Grouped with
            // app_version rather than alone: the same crash reappearing after
            // a release is the single most important thing this table can
            // tell anyone, and merging it into the old row would hide it.
            $table->char('fingerprint', 64);
            $table->string('app_version', 32)->nullable();

            $table->text('message');
            $table->longText('stack')->nullable();

            $table->string('platform', 16)->nullable();
            $table->string('device', 128)->nullable();
            $table->string('os_version', 32)->nullable();

            // The most recent person to hit it — who to call when the trace
            // alone isn't enough. Nulled rather than cascaded: a departed
            // employee must not take the bug report with them.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('occurrences')->default(1);

            // The device's own claim of when it happened, kept separately from
            // created_at for the same reason submitted_at is: crashes are
            // queued on the handset and sent when there is signal, which for
            // a crash is often much later.
            $table->timestamp('first_occurred_at');
            $table->timestamp('last_occurred_at');

            $table->timestamps();

            $table->unique(['fingerprint', 'app_version']);

            // The admin list is "worst first, newest first".
            $table->index(['last_occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_crash_reports');
    }
};
