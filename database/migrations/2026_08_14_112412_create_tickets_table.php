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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('buyer_name');
            $table->boolean('is_member')->default(false);
            // Free-text reference only — there's no member database to
            // validate against, the cashier judges membership visually.
            $table->string('member_reference')->nullable();
            $table->string('payment_method');
            // Snapshotted at sale time so an admin editing an event's prices
            // later never silently rewrites already-sold tickets' revenue.
            $table->unsignedInteger('price');
            $table->foreignId('sold_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['event_id', 'payment_method']);
            $table->index(['event_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
