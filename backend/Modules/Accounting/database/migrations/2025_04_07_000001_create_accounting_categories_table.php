<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.9/§4.8 — Income and Expense category masters, unified into
 * one table with a `kind` discriminator (rather than two near-identical
 * tables) since they're used identically everywhere except which
 * Voucher.type they're allowed to pair with (enforced in
 * StoreVoucherRequest, not at the DB level — a plain FK can't express
 * "only categories of the matching kind").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_categories', function (Blueprint $table) {
            $table->id();
            $table->enum('kind', ['income', 'expense'])->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_categories');
    }
};
