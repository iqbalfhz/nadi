<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a phone safely retry a submission it never got an answer for.
     *
     * A report filed in a basement is sent when signal returns, and the reply
     * can be lost on the way back — leaving the device unable to tell "saved"
     * from "never arrived". Retrying blind risks a duplicate; not retrying
     * risks losing the report. The client stamps each submission with a UUID,
     * and this table remembers what that UUID already produced.
     *
     * A table of its own rather than a column on each report table: the same
     * contract then covers actions that aren't creates (marking a delivery
     * done, say), where a naive replay would answer "already delivered" — an
     * error, for something that actually succeeded.
     */
    public function up(): void
    {
        Schema::create('api_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key', 64);

            // Method + path. A key replayed against a different endpoint is a
            // client bug, and this is what lets the server say so instead of
            // handing back an answer from an unrelated request.
            $table->string('endpoint');

            $table->unsignedSmallInteger('status');
            $table->json('response');
            $table->timestamps();

            // Scoped to the user, so one device can never replay another's key.
            $table->unique(['user_id', 'key']);

            // Supports the nightly cleanup of rows older than the retention
            // window (see routes/console.php).
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_idempotency_keys');
    }
};
