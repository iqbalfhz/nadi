<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Photos a phone has uploaded but not yet attached to a report.
     *
     * The mobile API splits "send the photo" from "send the report" because a
     * field worker's connection drops mid-upload as a matter of routine, not
     * as an edge case. Bundling both into one multipart request would mean
     * every retry re-sends every photo — three 10 MB files, from the start,
     * each time. Split, each photo that lands stays landed.
     *
     * Rows here are short-lived: the report create moves the file into the
     * model's media collection and deletes the row. What's left after that is
     * a photo whose report was never sent, cleaned up nightly.
     */
    public function up(): void
    {
        Schema::create('api_uploads', function (Blueprint $table) {
            // A UUID, not an auto-increment: this id travels to the phone and
            // back, and sequential ids would let one worker guess another's.
            $table->uuid('id')->primary();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Relative to the private 'internal' disk. Never a public URL —
            // these are evidence photos before they are anything else.
            $table->string('path');

            $table->string('mime_type', 64);
            $table->unsignedInteger('size');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_uploads');
    }
};
