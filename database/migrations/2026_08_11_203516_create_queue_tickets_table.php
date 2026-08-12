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
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('status')->default('waiting');
            $table->string('counter_label')->nullable();
            $table->foreignId('called_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->index(['queue_category_id', 'status', 'number']);
            $table->index(['queue_category_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
