<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v2 §3.17 / §4.4 — "Cutting is done against an approved Order +
 * Booking. A Cut Ticket is created per style/color/lay, recording fabric
 * consumption (pulled from Raw Material stock), planned quantity, and
 * cutting date."
 *
 * Simplification (documented in README "Known simplifications"): one
 * primary fabric per Cut Ticket (`raw_material_id` + `fabric_consumed`),
 * not a multi-material bill-of-materials. Trims (buttons, thread, etc.)
 * are not deducted at cutting time in v1 — only the fabric is.
 *
 * `status` starts `draft`; bundles/serials are only generated when the
 * ticket is explicitly finalized (see App/Services/CuttingService) — a
 * created-but-not-yet-finalized ticket doesn't touch stock or generate
 * any serials, so mistakes are cheap to just delete before finalizing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cut_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->restrictOnDelete();
            $table->string('style');
            $table->string('color');
            $table->string('size')->nullable();
            $table->date('cut_date');
            $table->foreignId('cutting_master_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->restrictOnDelete();
            $table->decimal('fabric_consumed', 15, 3);
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete(); // must be type=factory
            $table->unsignedInteger('bundle_size');
            $table->unsignedInteger('planned_quantity');
            $table->enum('status', ['draft', 'finalized'])->default('draft');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cut_tickets');
    }
};
