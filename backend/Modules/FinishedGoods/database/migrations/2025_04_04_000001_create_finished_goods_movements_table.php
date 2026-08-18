<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v2 §3.20 / §4.5 — Finished Goods stock, same ledger-as-source-of-
 * truth pattern as Modules/RawMaterial (sdd.md §5): there is no
 * `finished_goods_stock` table with a mutable quantity column. Stock at
 * a location for an order/style/color/size is SUM(quantity) over this
 * table. `quantity` is always ±1 here (one row per physical piece
 * movement) since `piece_serial_id` links each row back to the exact
 * unit that moved — "each finished-goods unit retains a link back to
 * its originating serial number(s) for full audit trail" (PRD v2
 * §3.20). `piece_serial_id` is nullable only for inward-subcontract
 * completions that don't go through the factory's own Cutting flow
 * (Phase 5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finished_goods_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->string('style');
            $table->string('color');
            $table->string('size')->nullable();
            $table->foreignId('piece_serial_id')->nullable()->constrained('piece_serials')->restrictOnDelete();
            $table->integer('quantity'); // signed: +1 intake/transfer-in, -1 transfer-out/shipment
            $table->enum('type', ['qc_intake', 'transfer_out', 'transfer_in', 'shipment', 'adjustment'])->index();
            $table->nullableMorphs('reference'); // StockTransfer, Shipment, ...
            $table->date('occurred_on');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finished_goods_movements');
    }
};
