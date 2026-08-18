<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §4.4 — Add New Booking's "extensive horizontally-scrolling table"
 * mirroring Order's per-style/color line items, plus fabric-consumption
 * fields specific to booking (DZN conversions, gray fabric/rib KG).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('style');
            $table->string('color');
            $table->date('shipment_date')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_value', 15, 2); // server-computed: quantity * unit_price
            $table->text('garment_description')->nullable();
            $table->string('garment_picture_path')->nullable();
            $table->string('pantone')->nullable();
            $table->string('body_fabrication')->nullable();
            $table->string('yarn_count')->nullable();
            $table->decimal('dzn_quantity', 12, 2)->nullable();
            $table->decimal('gray_fabric_consumption_kg', 12, 2)->nullable();
            $table->decimal('rib_consumption_kg', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_line_items');
    }
};
