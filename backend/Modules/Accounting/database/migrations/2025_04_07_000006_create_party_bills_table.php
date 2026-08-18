<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.10/§3.12 — the "Total Bill" half of a Party's financial
 * summary (Party Ledger / Party Due List). Append-only, same ledger
 * contract as everywhere else (sdd.md §5): a bill is a fact ("this
 * party was billed X on date Y"), never edited after the fact — correct
 * a mistake with an offsetting negative-amount entry, not a mutation.
 * The "Paid"/"Advance"/"Due"/"Balance" half comes from Voucher rows tied
 * to the same party — see App\Services\PartyFinancialsService for how
 * the two combine. Recorded manually by an Accountant in v1 (PRD's
 * "lightweight accounting suite" — see Accounting/README.md "Known
 * simplifications" for why this isn't auto-derived from Order/PO
 * totals).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('bill_date');
            $table->string('description')->nullable();
            $table->string('reference')->nullable(); // free-text, e.g. an Order/PO number
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_bills');
    }
};
