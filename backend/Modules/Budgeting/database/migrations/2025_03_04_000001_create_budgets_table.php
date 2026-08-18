<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.3 / §6.1(budget row) — "Tracks per-order budgeted quantity,
 * average unit price, and total value against status."
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->string('style')->nullable();
            $table->unsignedInteger('budgeted_quantity');
            $table->decimal('average_unit_price', 15, 2);
            // Server-computed: budgeted_quantity * average_unit_price.
            $table->decimal('total_value', 15, 2);
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
