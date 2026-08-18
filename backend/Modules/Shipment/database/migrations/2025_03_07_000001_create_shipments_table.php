<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.6 / §6 — "Shipments List / Add New Shipment: Tracks shipment
 * invoices (format SHIP-YYYY-NNNN) per order, including creator, total
 * quantity, and total CBM (cubic measurement)."
 *
 * Unlike Order's order_no (derived from the row's own auto-increment id —
 * see Modules/Order), `SHIP-YYYY-NNNN`'s NNNN resets per calendar year, so
 * it can't be purely id-derived. `sequence_no` + `year` are stored as
 * plain columns (not parsed back out of invoice_no) so the next-sequence
 * lookup is a fast indexed MAX() query — see
 * App/Services/ShipmentInvoiceNumberGenerator.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->nullable()->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence_no');
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('total_quantity');
            $table->decimal('total_cbm', 10, 3)->nullable();
            $table->date('shipment_date')->nullable();
            $table->enum('status', ['draft', 'shipped', 'delivered'])->default('draft');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['year', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
