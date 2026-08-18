<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v2 §3.19 / §4.3 — Stock Ledger. sdd.md §5: this table is the
 * source of truth for stock; `raw_materials.current_stock` does not
 * exist as a column. `quantity` is SIGNED (positive = stock in,
 * negative = stock out) — a plain SUM(quantity) gives the balance
 * without having to special-case `type` in every query. `type` is kept
 * purely for reporting/categorization ("what kind of movement was
 * this"), never for sign logic. Movements are only ever created through
 * App\Services\RawMaterialStockService — never directly mass-assigned
 * from a request — so the sign/type pairing can't drift out of sync.
 *
 * PRD v2 §3.21: "Raw material is factory/store-scoped only in v1" —
 * enforced in RawMaterialStockService, not at the DB level (a plain FK
 * can't express "only these two enum values of a related table").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->enum('type', ['receipt', 'issue', 'adjustment'])->index();
            $table->decimal('quantity', 15, 3); // signed
            $table->nullableMorphs('reference'); // PurchaseOrder, CutTicket, OutwardSubcontractOrder, ...
            $table->date('occurred_on');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_stock_movements');
    }
};
