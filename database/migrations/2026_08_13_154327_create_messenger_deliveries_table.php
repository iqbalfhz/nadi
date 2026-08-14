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
        Schema::create('messenger_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->string('destination');
            $table->string('document_description');
            $table->string('status')->default('available');
            $table->foreignId('messenger_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('in_transit_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            // "Tugas Terbuka" lists everything still Available; claiming
            // locks a specific row by id, so no other index is needed there.
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messenger_deliveries');
    }
};
