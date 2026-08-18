<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The bank balance ledger (sdd.md §5) — same shape as
 * raw_material_stock_movements: `amount` is SIGNED (deposit positive,
 * withdraw negative), `type` is for reporting only, never for sign
 * logic. Written only through App\Services\BankLedgerService — either
 * as a side effect of a Voucher (`reference` morphs to it) or a direct
 * manual adjustment (`reference` null, via BankAccountController's
 * deposit/withdraw actions — PRD's "Deposit/Withdraw actions" on the
 * Bank Accounts page, independent of the Voucher flow, e.g. an opening
 * balance or a bank fee with no party/category attached).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->enum('type', ['deposit', 'withdraw'])->index();
            $table->decimal('amount', 15, 2); // signed
            $table->nullableMorphs('reference'); // Voucher, Cheque (on pass), or null (manual adjustment)
            $table->date('occurred_on');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
