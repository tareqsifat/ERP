<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.11/§4.10/§5.5 — Employee directory. `salary` here is the
 * *current* agreed salary; a monthly payroll run snapshots it onto its
 * own SalaryPayment row (salary_amount) rather than reading this column
 * live, so a later raise doesn't rewrite history for already-paid
 * months. NID/passport uploads stored the same way as Party's image
 * (sdd.md §8: outside the public web root).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('employment_type')->nullable(); // e.g. full-time, part-time, contract
            $table->date('birth_date')->nullable();
            $table->date('joining_date');
            $table->foreignId('designation_id')->constrained('designations')->restrictOnDelete();
            $table->decimal('salary', 15, 2);
            $table->string('id_document_path')->nullable();
            $table->string('id_document_back_path')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
