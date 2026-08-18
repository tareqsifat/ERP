<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.2 / §4.4 / §6.2 — Booking Management. A Booking always
 * references an existing Order (fabric/specification detail layered on
 * top of the order's commercial line items).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('preparer_id')->constrained('users')->restrictOnDelete();
            $table->date('booking_date');
            $table->text('composition')->nullable();
            $table->decimal('process_loss_percent', 5, 2)->nullable();
            $table->text('other_fabrics')->nullable();
            $table->string('rib')->nullable();
            $table->string('collar')->nullable();
            $table->string('item_image_path')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
