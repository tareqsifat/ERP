<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.9/§4.8 — Bank Accounts directory. `amount`/"running balance"
 * shown in the PRD table is deliberately NOT a stored column here —
 * same sdd.md §5 ledger principle as everywhere else in this system: the
 * balance is SUM(signed amount) over bank_transactions
 * (App\Services\BankLedgerService::balanceOf()), never a mutable cache
 * that can drift.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_holder_name');
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('branch_name')->nullable();
            $table->string('routing_swift_no')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
