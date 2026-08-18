<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v2 §3.23/§3.24/§4.8 — shared shape for Outward and Inward
 * Subcontract Orders (`direction` is the only thing that structurally
 * differs). `subcontract_no` follows the same `SC-YYYY-NNNN` race-safe
 * per-year-sequence pattern as Shipment's invoice_no / RawMaterial's
 * po_no / Location's transfer_no (App\Services\SubcontractNumberGenerator).
 *
 * `order_id` is nullable at the schema level but required by
 * StoreSubcontractOrderRequest for `direction=outward` (references the
 * factory's own Order/style being subcontracted out) — left optional
 * for `direction=inward`, where it's purely informational ("which of
 * our own orders does this job-work capacity displace"), since PRD v2
 * §3.24 only ties Inward to "the external party" and a style, not one
 * of this factory's Orders. This is independent of CutTicket.order_id,
 * which stays mandatory either way (see the
 * add_inward_subcontract_order_id_to_cut_tickets_table migration) — an
 * inward job still needs a real Order to hang its Cut Ticket's serial
 * numbers off of (`{OrderNo}-...`), it just doesn't have to be *this*
 * order_id.
 *
 * `raw_material_id`/`raw_material_quantity` is the single-material
 * simplification also used by CutTicket (Modules/Production) — one
 * primary fabric issued per order, not a multi-material BOM.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcontract_orders', function (Blueprint $table) {
            $table->id();
            $table->string('subcontract_no')->nullable()->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence_no');
            $table->enum('direction', ['outward', 'inward']);
            $table->foreignId('party_id')->constrained('parties')->restrictOnDelete(); // must be type=subcontractor
            $table->foreignId('order_id')->nullable()->constrained('orders')->restrictOnDelete();
            $table->string('style');
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->decimal('rate', 15, 2);
            $table->enum('rate_unit', ['piece', 'dozen']);
            $table->unsignedInteger('quantity_expected');
            $table->foreignId('raw_material_id')->nullable()->constrained('raw_materials')->restrictOnDelete();
            $table->decimal('raw_material_quantity', 15, 3)->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->date('expected_date')->nullable();
            $table->enum('status', ['open', 'partially_returned', 'closed'])->default('open');
            $table->decimal('job_work_income_amount', 15, 2)->nullable(); // inward only, set on dispatch-back
            $table->timestamp('dispatched_back_at')->nullable(); // inward only
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['year', 'sequence_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcontract_orders');
    }
};
