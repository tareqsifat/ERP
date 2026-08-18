<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.4 / §6 — "Sample List / Add New Sample: Tracks sample
 * requests per order including consignee, style number, item, sample
 * type, and garment quantity with status."
 *
 * `sample_type` enum values (Proto/Fit/PP/Size Set/Shipment/Salesman) are
 * standard garment-export sampling stage terminology — the PRD names the
 * field but doesn't enumerate values; flag during Phase 3 review if the
 * client uses different terms (see README).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->string('consignee')->nullable();
            $table->string('style_number')->nullable();
            $table->string('item')->nullable();
            $table->enum('sample_type', ['proto', 'fit', 'pp', 'size_set', 'shipment', 'salesman'])->nullable();
            $table->unsignedInteger('quantity');
            $table->enum('status', ['requested', 'sent', 'approved', 'rejected'])->default('requested');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};
