<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checklist HK — NADI.MD 4.7.
 *
 * One migration for all three tables because they are meaningless apart: a
 * category with no areas has nothing to inspect, and an inspection needs both.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hk_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Switches the "Lantai" field on for this category. Kept as data
            // rather than hardcoded because the category list is not final —
            // NADI.MD still calls "5-10 categories" an estimate, and every new
            // one would otherwise need a code change and a deploy.
            $table->boolean('requires_floor')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hk_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hk_category_id')->constrained()->restrictOnDelete();
            // Free text on purpose: each category names its points its own way
            // ("Lt 2 Zona A" for Toilet, something else elsewhere), so there is
            // no uniform floor/zone structure to model.
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['hk_category_id', 'name']);
        });

        Schema::create('hk_inspections', function (Blueprint $table) {
            $table->id();
            // Denormalised from the chosen area at submit time, never taken
            // from client input — reports are filtered by category constantly,
            // and this keeps those queries flat. Same reasoning as
            // vendor_sales.bazaar_id.
            $table->foreignId('hk_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('hk_area_id')->constrained()->restrictOnDelete();
            // The supervisor filing the report, from the logged-in user.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // The housekeeping staff being inspected. Typed by hand rather than
            // linked to users: HK staff are not NADI account holders.
            $table->string('staff_name');
            $table->string('shift');
            $table->string('condition');
            // Only collected for categories flagged requires_floor. For Toilet
            // the floor is already part of the point's name ("Lt 2 Zona A"), so
            // a column there would just duplicate it.
            $table->string('floor')->nullable();
            $table->text('notes')->nullable();
            // Required by the form whenever the condition is not "Bersih";
            // nullable here because a clean report legitimately has none.
            $table->text('follow_up')->nullable();
            $table->timestamps();

            $table->index(['hk_category_id', 'created_at']);
            $table->index(['condition', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hk_inspections');
        Schema::dropIfExists('hk_areas');
        Schema::dropIfExists('hk_categories');
    }
};
