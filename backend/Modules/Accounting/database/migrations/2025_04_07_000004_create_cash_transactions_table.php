<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.9/§4.8 — Cash in Hand. One ledger for the whole factory (not
 * per-location — PRD shows a single "Cash in Hand" page, no location
 * picker), same signed-amount ledger shape as bank_transactions.
 * `note` is the "Bank/Account Name Note" free-text column from the PRD
 * table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['increase', 'reduce'])->index();
            $table->decimal('amount', 15, 2); // signed
            $table->string('note')->nullable();
            $table->nullableMorphs('reference'); // Voucher or null (manual adjustment)
            $table->date('occurred_on');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
