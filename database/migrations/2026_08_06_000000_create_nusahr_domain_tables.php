<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('employee')->index();
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->string('code')->unique(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('positions', function (Blueprint $table) {
            $table->id(); $table->foreignId('department_id')->constrained()->restrictOnDelete(); $table->string('name'); $table->boolean('is_active')->default(true); $table->timestamps(); $table->unique(['department_id', 'name']);
        });
        Schema::create('locations', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->string('timezone')->default('Asia/Makassar'); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete(); $table->string('employee_number')->unique(); $table->foreignId('department_id')->constrained()->restrictOnDelete(); $table->foreignId('position_id')->constrained()->restrictOnDelete(); $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete(); $table->string('phone')->nullable(); $table->date('joined_at'); $table->date('ended_at')->nullable(); $table->decimal('basic_salary', 15, 2)->default(0); $table->string('bank_account')->nullable(); $table->timestamps(); $table->index(['department_id', 'manager_id']);
        });
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->unsignedSmallInteger('annual_quota')->default(12); $table->boolean('is_paid')->default(true); $table->boolean('requires_attachment')->default(false); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('holidays', function (Blueprint $table) {
            $table->id(); $table->date('date')->unique(); $table->string('name'); $table->timestamps();
        });
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); $table->foreignId('leave_type_id')->constrained()->restrictOnDelete(); $table->unsignedSmallInteger('year'); $table->decimal('entitled', 6, 2); $table->decimal('used', 6, 2)->default(0); $table->decimal('pending', 6, 2)->default(0); $table->timestamps(); $table->unique(['employee_id', 'leave_type_id', 'year']);
        });
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); $table->foreignId('leave_type_id')->constrained()->restrictOnDelete(); $table->date('start_date'); $table->date('end_date'); $table->decimal('days', 6, 2); $table->text('reason'); $table->string('attachment_path')->nullable(); $table->string('status')->default('pending')->index(); $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('reviewed_at')->nullable(); $table->text('review_notes')->nullable(); $table->timestamps(); $table->index(['employee_id', 'start_date', 'end_date']);
        });
        Schema::create('attendances', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); $table->date('date'); $table->timestamp('checked_in_at')->nullable(); $table->timestamp('checked_out_at')->nullable(); $table->unsignedInteger('worked_minutes')->default(0); $table->unsignedInteger('late_minutes')->default(0); $table->string('status')->default('present')->index(); $table->text('correction_reason')->nullable(); $table->timestamps(); $table->unique(['employee_id', 'date']);
        });
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->date('starts_on'); $table->date('ends_on'); $table->string('status')->default('draft')->index(); $table->timestamp('published_at')->nullable(); $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); $table->unique(['starts_on', 'ends_on']);
        });
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id(); $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete(); $table->foreignId('employee_id')->constrained()->restrictOnDelete(); $table->decimal('basic_salary', 15, 2); $table->decimal('earnings', 15, 2)->default(0); $table->decimal('deductions', 15, 2)->default(0); $table->decimal('net_salary', 15, 2); $table->json('breakdown')->nullable(); $table->timestamps(); $table->unique(['payroll_period_id', 'employee_id']);
        });
        Schema::create('announcements', function (Blueprint $table) {
            $table->id(); $table->foreignId('author_id')->constrained('users')->restrictOnDelete(); $table->string('title'); $table->text('body'); $table->string('audience')->default('all'); $table->timestamp('published_at')->nullable()->index(); $table->timestamps();
        });
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete(); $table->string('name'); $table->string('path'); $table->string('mime_type'); $table->unsignedBigInteger('size'); $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('event')->index(); $table->string('auditable_type')->nullable(); $table->unsignedBigInteger('auditable_id')->nullable(); $table->json('metadata')->nullable(); $table->string('ip_address', 45)->nullable(); $table->timestamps(); $table->index(['auditable_type', 'auditable_id']);
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id(); $table->string('key')->unique(); $table->text('value')->nullable(); $table->boolean('is_public')->default(false); $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'audit_logs', 'employee_documents', 'announcements', 'payroll_records', 'payroll_periods', 'attendances', 'leave_requests', 'leave_balances', 'holidays', 'leave_types', 'employees', 'locations', 'positions', 'departments'] as $table) { Schema::dropIfExists($table); }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'is_active']));
    }
};
