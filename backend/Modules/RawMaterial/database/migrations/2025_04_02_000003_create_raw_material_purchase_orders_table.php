<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD v2 §3.19 — Purchase Orders (evolves v1 Accessory Orders). po_no
// follows the same auto-number pattern as Modules/Shipment's invoice_no
// (year-scoped sequence, see App/Services/PurchaseOrderNumberGenerator).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_no')->nullable()->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence_no');
            $table->foreignId('supplier_id')->constrained('parties')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete(); // receiving location
            $table->enum('status', ['draft', 'ordered', 'partially_received', 'received', 'cancelled'])->default('draft');
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['year', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_purchase_orders');
    }
};
