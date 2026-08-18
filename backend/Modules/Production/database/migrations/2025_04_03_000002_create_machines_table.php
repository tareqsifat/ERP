<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD v2 §3.22 / §4.7 — register + assignment only, not real-time IoT
// monitoring (explicitly Out of Scope, PRD v2 §7).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('tag')->unique(); // e.g. machine ID/asset tag
            $table->string('type'); // e.g. single needle, overlock, flatlock
            $table->enum('status', ['active', 'maintenance', 'idle'])->default('active');
            $table->foreignId('line_id')->nullable()->constrained('lines')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
