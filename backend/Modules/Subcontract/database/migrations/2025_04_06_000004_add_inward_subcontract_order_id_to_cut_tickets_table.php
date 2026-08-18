<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v2 §3.24 — Inward Subcontract work "is processed through the
 * normal Cutting/Sewing/QC flow (§3.17-3.18), tagged as inward-
 * subcontract so it doesn't get counted as the factory's own finished
 * goods stock for sale." This nullable FK is that tag: when set,
 * Modules/Production/App/Services/QcService::pass() stops a passed
 * piece at `qc_passed` instead of auto-progressing it into the
 * factory's own Finished Goods ledger — see QcService's updated
 * docblock and Modules/Subcontract/App/Services/
 * SubcontractInwardService::dispatchBack(), which is the only other
 * place those pieces move (straight to `shipped`, skipping Finished
 * Goods entirely).
 *
 * Lives in Modules/Subcontract's migrations (not Modules/Production's)
 * because it's a Subcontract-owned concept being grafted onto
 * Production's table — matches how Modules/Location's
 * add_foreign_key_to_users_location_id migration alters a table it
 * doesn't own for the same reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cut_tickets', function (Blueprint $table) {
            $table->foreignId('inward_subcontract_order_id')
                ->nullable()
                ->after('booking_id')
                ->constrained('subcontract_orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cut_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inward_subcontract_order_id');
        });
    }
};
