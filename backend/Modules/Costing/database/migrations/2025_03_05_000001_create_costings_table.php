<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.3 — "Costing List / Costing Form: Mirrors the budget
 * structure for cost tracking per order and style."
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->string('style')->nullable();
            $table->unsignedInteger('costed_quantity');
            $table->decimal('average_unit_cost', 15, 2);
            // Server-computed: costed_quantity * average_unit_cost.
            $table->decimal('total_cost', 15, 2);
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costings');
    }
};
