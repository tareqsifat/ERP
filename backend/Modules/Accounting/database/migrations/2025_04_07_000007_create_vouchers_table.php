<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.9/§4.8 — Credit/Debit Vouchers, the central transaction
 * record. `voucher_no` is `CR-YYYY-NNNN` (credit) / `DR-YYYY-NNNN`
 * (debit) — separate per-type sequences (App\Services\
 * VoucherNumberGenerator), same race-safe per-year-sequence pattern as
 * every other numbered document in this system.
 *
 * `purpose`: `payment`/`advance` require `party_id` — a real money
 * movement to/from a party, posting a PartyBill-adjacent ledger entry
 * via App\Services\PartyFinancialsService's "paid"/"advance" totals;
 * `general` is a plain income/expense entry (e.g. office rent) with no
 * party.
 *
 * `payment_type` drives which ledger this posts to
 * (App\Services\VoucherService::record()): `cash` → cash_transactions,
 * `bank` → bank_transactions (via `bank_account_id`), `cheque` → no
 * immediate ledger effect — the linked Cheque (`cheque_id`) posts to
 * the bank ledger only once ChequeService::markPassed() clears it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->nullable()->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence_no');
            $table->enum('type', ['credit', 'debit']);
            $table->enum('purpose', ['payment', 'advance', 'general'])->default('general');
            $table->foreignId('party_id')->nullable()->constrained('parties')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('accounting_categories')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->enum('payment_type', ['cash', 'bank', 'cheque']);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->restrictOnDelete();
            $table->foreignId('cheque_id')->nullable()->constrained('cheques')->restrictOnDelete();
            $table->date('date');
            $table->string('bill_no')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['type', 'year', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
