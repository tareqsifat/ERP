<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.1 / §4.3 / §6.1 — Orders Management.
 *
 * sdd.md §5: money columns decimal(15,2); soft deletes; FK `restrict` on
 * delete for party/merchandiser since Orders are the traceability/financial
 * root everything else in this system hangs off of.
 *
 * `order_no` is nullable at the DB level (unique index still applies —
 * MySQL allows multiple NULLs under a unique index) purely so the row can
 * be inserted first and get its auto-increment `id`, then have `order_no`
 * derived from that `id` and written back inside the same DB transaction
 * (see Modules/Order/App/Services/OrderNumberGenerator). No row is ever
 * left with a NULL order_no once the creating transaction commits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->nullable()->unique();
            $table->foreignId('party_id')->constrained('parties')->restrictOnDelete();
            $table->foreignId('merchandiser_id')->constrained('users')->restrictOnDelete();
            $table->string('item_image_path')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('fabrication')->nullable();
            $table->string('gsm')->nullable();
            $table->string('yarn_count')->nullable();
            $table->enum('shipment_mode', ['sea', 'air', 'sea_air', 'road', 'courier']);
            $table->enum('payment_mode', ['lc', 'tt', 'advance', 'on_delivery']);
            $table->string('bank_account_name')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('season')->nullable();
            $table->string('pantone')->nullable();
            $table->text('consignee')->nullable();
            $table->text('notify_party')->nullable();
            $table->date('contract_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedSmallInteger('negotiation_period_days')->nullable();
            $table->string('port_of_loading')->nullable();
            $table->string('port_of_discharge')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending', 'approved', 'in_production', 'shipped', 'completed', 'cancelled'])
                ->default('pending');
            // sdd.md §5: cached for read performance, but order_line_items
            // is the source of truth — always recomputed server-side
            // whenever line items change, never trusted from client input.
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
