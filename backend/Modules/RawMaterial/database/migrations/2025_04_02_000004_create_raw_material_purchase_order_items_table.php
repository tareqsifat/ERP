<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('raw_material_purchase_orders')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->restrictOnDelete();
            $table->decimal('quantity_ordered', 15, 3);
            // Cached running total, only ever written by
            // PurchaseOrderReceiptService — never client-mass-assigned.
            $table->decimal('quantity_received', 15, 3)->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2); // server-computed: quantity_ordered * unit_price
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_purchase_order_items');
    }
};
