<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §4.3 — Add New Order's dynamic multi-row line-item table
 * (style, color, item, shipment date, quantity, unit price, computed total).
 *
 * sdd.md §5: cascade delete is acceptable here specifically because line
 * items are genuinely dependent child rows of an order (the sdd's own
 * example) — but in practice orders are soft-delete only at the app layer,
 * so this FK cascade only ever fires on a real hard delete (e.g. test
 * cleanup), never in normal operation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('style');
            $table->string('color');
            $table->string('item');
            $table->date('shipment_date')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 2);
            // Server-computed (quantity * unit_price) — never trust a
            // client-sent total_price; see OrderController@recalculateLineItem.
            $table->decimal('total_price', 15, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_line_items');
    }
};
