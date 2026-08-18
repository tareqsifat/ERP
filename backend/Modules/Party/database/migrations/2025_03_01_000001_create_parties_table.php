<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §6.3 (Party) + PRD v2 §4.9 (Type extended with Subcontractor).
 *
 * sdd.md §5: soft deletes (parties are referenced by Orders / vouchers /
 * subcontract orders once those exist — never hard-delete), money columns
 * are decimal(15,2), never float.
 *
 * PRD v2 §7 "Out of Scope": no portal login for v1 — this table
 * deliberately has no `password` column. `email`/`phone` are plain
 * contact fields, not login credentials.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['buyer', 'supplier', 'subcontractor'])->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('country')->nullable();
            $table->enum('opening_balance_type', ['debit', 'credit'])->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
