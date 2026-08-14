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
        Schema::create('security_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Random token embedded in the printed QR code's URL — never typed
            // by a human, so it doesn't need to be memorable, just unique.
            $table->string('code', 32)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_checkpoints');
    }
};
