<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v2 §3.23 — Subcontractor Ledger: "running balance of value issued
 * vs. value returned/billed vs. paid." Implemented as an append-only
 * ledger (sdd.md §5's pattern, same as the stock ledgers) rather than a
 * mutable balance column — `amount` is always a positive value, its
 * meaning ("increases what the factory owes" vs. "increases what's
 * receivable from the external party") comes from `type`, not a sign.
 * `party_id` is denormalized off subcontract_order.party_id so "give me
 * this subcontractor's whole ledger across every order" doesn't need a
 * join on every query (same reasoning as FinishedGoodsMovement's
 * denormalized order_id).
 *
 * Full Party Due List / voucher integration is Phase 6 (Accounting) —
 * this table is the source those will read from once built; see
 * Modules/Subcontract/README.md "Depends on / depended on by".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcontract_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcontract_order_id')->constrained('subcontract_orders')->restrictOnDelete();
            $table->foreignId('party_id')->constrained('parties')->restrictOnDelete();
            $table->enum('type', ['issue_value', 'return_value', 'shortage_deduction', 'job_work_income', 'payment']);
            $table->decimal('amount', 15, 2);
            $table->date('occurred_on');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcontract_ledger_entries');
    }
};
