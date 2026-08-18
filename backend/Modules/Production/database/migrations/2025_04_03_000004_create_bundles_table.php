<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v2 §3.17 / §4.4 — generated automatically when a Cut Ticket is
 * finalized (App/Services/CuttingService), never created directly by a
 * client request.
 *
 * `status` here is a bundle-level convenience cache updated by bulk
 * line-input/output actions (App/Services/SewingService) — it is NOT
 * the source of truth once QC has run, because QC operates per piece
 * and pieces within one bundle can diverge (some pass, some reject).
 * PieceSerial.status is always the authoritative per-unit state; see
 * Modules/Production/README.md "Bundle status vs. piece status".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cut_ticket_id')->constrained('cut_tickets')->restrictOnDelete();
            $table->string('bundle_no'); // sequential within its cut ticket, e.g. "003"
            $table->unsignedInteger('quantity');
            $table->foreignId('line_id')->nullable()->constrained('lines')->nullOnDelete();
            $table->enum('status', ['cut', 'in_sewing', 'sewn'])->default('cut');
            $table->timestamp('assigned_to_line_at')->nullable();
            $table->timestamp('line_output_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cut_ticket_id', 'bundle_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundles');
    }
};
