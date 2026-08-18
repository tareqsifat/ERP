<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.9/§4.8 — Cheques (Passed/Unused tabs). A cheque is issued or
 * received `unused` and only affects the bank ledger once it actually
 * clears — `App\Services\ChequeService::markPassed()` is the one place
 * that posts a bank_transactions row (via `bank_account_id`) and flips
 * `status`. This deliberately models real clearing lag rather than
 * assuming a cheque hits the bank balance the moment it's written.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->nullable()->constrained('parties')->restrictOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->string('cheque_no');
            $table->decimal('amount', 15, 2);
            $table->date('issue_date');
            $table->enum('type', ['income', 'expense']); // income = we received it, expense = we issued it
            $table->enum('status', ['unused', 'passed'])->default('unused')->index();
            $table->timestamp('passed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
