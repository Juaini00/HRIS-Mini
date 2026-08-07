<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('type'); $table->string('calculation_type'); $table->decimal('value', 15, 4); $table->boolean('is_taxable')->default(false); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); $table->foreignId('salary_component_id')->constrained()->cascadeOnDelete(); $table->decimal('override_value', 15, 4)->nullable(); $table->date('effective_from'); $table->date('effective_to')->nullable(); $table->timestamps(); $table->unique(['employee_id', 'salary_component_id', 'effective_from'], 'employee_component_effective_unique');
        });
        Schema::create('payroll_record_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('payroll_record_id')->constrained()->cascadeOnDelete(); $table->foreignId('salary_component_id')->nullable()->constrained()->nullOnDelete(); $table->string('name'); $table->string('type'); $table->decimal('amount', 15, 2); $table->boolean('is_manual')->default(false); $table->text('notes')->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payroll_record_items'); Schema::dropIfExists('employee_salary_components'); Schema::dropIfExists('salary_components'); }
};
