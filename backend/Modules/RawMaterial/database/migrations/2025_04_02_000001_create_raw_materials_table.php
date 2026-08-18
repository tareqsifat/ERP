<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v2 §3.19 / §4.2 — Raw Material Master. `unit` is a plain string
 * (kg/meter/pcs/cone/roll…) rather than a `unit_id` FK to a dedicated
 * Units module — no such module was scaffolded (sdd.md §2's module list
 * has no standalone "Unit"), and a whole module for a 4-row lookup table
 * would be over-engineering; same pragmatic call as Order's
 * `bank_account_name` string field. `current_stock` is deliberately NOT a
 * column — sdd.md §5: computed from raw_material_stock_movements, the
 * ledger is the source of truth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('category', ['fabric', 'trim', 'packaging', 'other'])->index();
            $table->string('unit');
            $table->decimal('reorder_level', 15, 3)->default(0);
            $table->foreignId('default_supplier_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_materials');
    }
};
