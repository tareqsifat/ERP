<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v2 §3.21 — Stock Transfer between locations (typically Main Store
 * → Showroom, but any two Finished-Goods-capable locations are allowed;
 * the service layer decides which location pairs make sense, not the
 * schema). `transfer_no` follows the same `PREFIX-YYYY-NNNN` race-safe
 * per-year-sequence pattern as Shipment's invoice_no and RawMaterial's
 * po_no (App\Services\StockTransferNumberGenerator).
 *
 * Dispatch and receive are two separate steps (PRD v2 §3.21: "dispatch/
 * receive between Main Store and Showrooms") — `quantity_dispatched` is
 * set at creation, `quantity_received` stays null until receipt, so a
 * short/over receipt is visible as a discrepancy rather than silently
 * assumed equal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no')->nullable()->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence_no');
            $table->foreignId('from_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('to_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->string('style');
            $table->string('color');
            $table->string('size')->nullable();
            $table->unsignedInteger('quantity_dispatched');
            $table->unsignedInteger('quantity_received')->nullable();
            $table->enum('status', ['dispatched', 'received', 'discrepancy'])->default('dispatched');
            $table->foreignId('dispatched_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('dispatched_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['year', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
