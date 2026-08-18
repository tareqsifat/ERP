<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// users.location_id was created nullable/indexed but without an FK in
// Phase 1 (Modules/Location didn't exist yet) — see
// database/migrations/0001_01_01_000000_create_users_table.php. Wiring
// the FK here, once locations exists, per sdd.md §4's location-scoping
// note on User.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
        });
    }
};
