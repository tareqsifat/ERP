<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD v1 §3.15/§4.13 — Currency, Notifications, System (multi-tab), and
// Company Settings. A simple key/value store rather than one table per
// settings group: the PRD describes these as a handful of admin-tunable
// fields, not a relational domain of their own, and a key/value table
// lets Modules/Setting/database/seeders/SettingSeeder.php seed sane
// defaults without a migration per new field later.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            // JSON so a single row can hold either a scalar (e.g. the
            // currency code) or a small structured value (e.g. the
            // notification-preferences bag) without a schema change.
            $table->json('value')->nullable();
            $table->enum('group', ['currency', 'notification', 'system', 'company'])->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
