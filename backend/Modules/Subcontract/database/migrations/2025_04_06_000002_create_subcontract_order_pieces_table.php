<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v2 §3.23 — Outward Subcontract's piece-level issue/return
 * tracking. Deliberately does NOT mutate `piece_serials.status` while a
 * piece is with a subcontractor (see Modules/Subcontract/README.md
 * "Why PieceSerial.status isn't touched during a subcontract cycle") —
 * this table's `resolved_at`/`resolution` IS the source of truth for
 * "is this piece currently away at a subcontractor," the same way
 * stock ledgers are the source of truth instead of a mutable balance
 * column (sdd.md §5).
 *
 * `resolution` is null while the piece is still outstanding at the
 * subcontractor; `returned` once QC-ready pieces come back, `written_off`
 * for a piece confirmed lost/damaged that will never return (PRD v2
 * §3.23 "Shortages/damages are recorded and reflected in the
 * Subcontractor Ledger").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcontract_order_pieces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcontract_order_id')->constrained('subcontract_orders')->restrictOnDelete();
            $table->foreignId('piece_serial_id')->constrained('piece_serials')->restrictOnDelete();
            $table->timestamp('issued_at');
            $table->enum('resolution', ['returned', 'written_off'])->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Explicit short name: the auto-generated name (table + both
            // column names + "_unique") is 68 characters, over MySQL's
            // 64-character identifier limit.
            $table->unique(['subcontract_order_id', 'piece_serial_id'], 'subcontract_order_pieces_order_piece_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcontract_order_pieces');
    }
};
