<?php

use App\Enums\AnnouncementAudienceType;
use App\Enums\AnnouncementStatus;
use App\Enums\AttendanceSource;
use App\Enums\DocumentVisibility;
use App\Enums\EmploymentStatus;
use App\Enums\LeaveSession;
use App\Enums\WorkMode;
use App\Enums\WorkScheduleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the MVP schema up to the full HRIS specification.
 *
 * Everything here is additive: existing columns keep their data, so this can run
 * against a seeded database without losing history.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createCompanies();
        $this->expandOrganization();
        $this->expandEmployees();
        $this->expandDocuments();
        $this->expandLeave();
        $this->expandAttendance();
        $this->expandPayroll();
        $this->expandAnnouncements();
        $this->expandAuditLogs();
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_audiences');
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('companies');

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn([
                'first_name', 'last_name', 'preferred_name', 'work_email', 'personal_email',
                'gender', 'date_of_birth', 'place_of_birth', 'nationality', 'marital_status',
                'photo_path', 'probation_ends_on', 'contract_starts_on', 'contract_ends_on',
                'employment_status', 'terminated_on', 'work_schedule_type', 'bank_name',
                'bank_account_holder', 'tax_number', 'address', 'city', 'province',
                'postal_code', 'country', 'emergency_contact_name',
                'emergency_contact_relationship', 'emergency_contact_phone', 'notes',
                'created_by', 'updated_by',
            ]);
        });

        Schema::table('departments', fn (Blueprint $table) => $table->dropColumn(['description', 'parent_id', 'manager_id', 'created_by', 'updated_by']));
        Schema::table('positions', fn (Blueprint $table) => $table->dropColumn(['code', 'description', 'level', 'min_salary', 'max_salary']));
        Schema::table('locations', fn (Blueprint $table) => $table->dropColumn(['code', 'address', 'city', 'province', 'country', 'latitude', 'longitude', 'attendance_radius_meters']));
        Schema::table('employment_types', fn (Blueprint $table) => $table->dropColumn(['code', 'description']));
        Schema::table('holidays', fn (Blueprint $table) => $table->dropColumn(['description', 'is_recurring', 'location_id', 'is_active']));
        Schema::table('employee_documents', fn (Blueprint $table) => $table->dropColumn(['title', 'original_filename', 'visibility', 'description']));
        Schema::table('leave_types', fn (Blueprint $table) => $table->dropColumn(['code', 'description', 'max_consecutive_days', 'min_notice_days', 'allows_negative_balance', 'carry_forward_enabled', 'max_carry_forward_days', 'color']));
        Schema::table('leave_balances', fn (Blueprint $table) => $table->dropColumn(['carried_forward', 'adjustment', 'last_recalculated_at']));
        Schema::table('leave_requests', fn (Blueprint $table) => $table->dropColumn(['request_number', 'start_session', 'end_session', 'current_approver_id', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason', 'submitted_by']));
        Schema::table('attendances', fn (Blueprint $table) => $table->dropColumn(['break_minutes', 'overtime_minutes', 'work_mode', 'source', 'location_id', 'check_in_notes', 'check_out_notes', 'created_by', 'updated_by']));
        Schema::table('payroll_periods', fn (Blueprint $table) => $table->dropColumn(['year', 'month', 'payment_date', 'generated_at', 'generated_by', 'notes']));
        Schema::table('payroll_records', fn (Blueprint $table) => $table->dropColumn(['total_earnings', 'total_deductions', 'gross_salary', 'working_days', 'present_days', 'paid_leave_days', 'unpaid_leave_days', 'absent_days', 'overtime_minutes', 'generated_at']));
        Schema::table('salary_components', fn (Blueprint $table) => $table->dropColumn(['code', 'description']));
        Schema::table('announcements', fn (Blueprint $table) => $table->dropColumn(['slug', 'summary', 'status', 'audience_type', 'expires_at', 'is_pinned', 'updated_by']));
        Schema::table('audit_logs', fn (Blueprint $table) => $table->dropColumn(['event_category', 'description', 'old_values', 'new_values', 'user_agent']));
    }

    /**
     * Single-company profile. Kept as a table rather than config so HR can edit it.
     */
    private function createCompanies(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('code')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Indonesia');
            $table->string('logo_path')->nullable();
            $table->string('timezone')->default('Asia/Makassar');
            $table->string('currency', 3)->default('IDR');
            $table->time('attendance_starts_at')->default('09:00');
            $table->time('attendance_ends_at')->default('17:00');
            $table->unsignedSmallInteger('attendance_grace_minutes')->default(15);
            $table->unsignedSmallInteger('default_annual_leave_days')->default(12);
            $table->unsignedTinyInteger('payroll_cutoff_day')->default(25);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function expandOrganization(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('positions', function (Blueprint $table): void {
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('level')->default(1);
            $table->decimal('min_salary', 15, 2)->nullable();
            $table->decimal('max_salary', 15, 2)->nullable();
        });

        Schema::table('locations', function (Blueprint $table): void {
            $table->string('code')->nullable()->unique();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->default('Indonesia');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('attendance_radius_meters')->nullable();
        });

        Schema::table('employment_types', function (Blueprint $table): void {
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
        });

        Schema::table('holidays', function (Blueprint $table): void {
            $table->text('description')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->index(['date', 'is_active']);
        });
    }

    private function expandEmployees(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('preferred_name')->nullable();
            $table->string('work_email')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('photo_path')->nullable();
            $table->date('probation_ends_on')->nullable();
            $table->date('contract_starts_on')->nullable();
            $table->date('contract_ends_on')->nullable();
            $table->string('employment_status')->default(EmploymentStatus::Active->value);
            $table->date('terminated_on')->nullable();
            $table->string('work_schedule_type')->default(WorkScheduleType::Office->value);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->text('tax_number')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique('work_email');
            $table->index('employment_status');
            $table->index(['department_id', 'employment_status']);
            $table->index('manager_id');
            $table->index('contract_ends_on');
            $table->index('probation_ends_on');
        });
    }

    private function expandDocuments(): void
    {
        Schema::table('employee_documents', function (Blueprint $table): void {
            $table->string('title')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('visibility')->default(DocumentVisibility::HrOnly->value);
            $table->text('description')->nullable();
            $table->index(['employee_id', 'category']);
        });
    }

    private function expandLeave(): void
    {
        Schema::table('leave_types', function (Blueprint $table): void {
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('max_consecutive_days')->nullable();
            $table->unsignedSmallInteger('min_notice_days')->default(0);
            $table->boolean('allows_negative_balance')->default(false);
            $table->boolean('carry_forward_enabled')->default(false);
            $table->unsignedSmallInteger('max_carry_forward_days')->nullable();
            $table->string('color', 20)->default('#64748b');
        });

        Schema::table('leave_balances', function (Blueprint $table): void {
            $table->decimal('carried_forward', 6, 2)->default(0);
            $table->decimal('adjustment', 6, 2)->default(0);
            $table->timestamp('last_recalculated_at')->nullable();
        });

        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->string('request_number')->nullable()->unique();
            $table->string('start_session')->default(LeaveSession::FullDay->value);
            $table->string('end_session')->default(LeaveSession::FullDay->value);
            $table->foreignId('current_approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['employee_id', 'status']);
            $table->index(['status', 'start_date']);
        });
    }

    private function expandAttendance(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->unsignedInteger('break_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->string('work_mode')->default(WorkMode::Office->value);
            $table->string('source')->default(AttendanceSource::SelfService->value);
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->text('check_in_notes')->nullable();
            $table->text('check_out_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['date', 'status']);
        });

        Schema::create('attendance_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('reason');
            $table->text('review_notes')->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['attendance_id', 'status']);
        });
    }

    private function expandPayroll(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->index(['status', 'ends_on']);
        });

        Schema::table('payroll_records', function (Blueprint $table): void {
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('working_days', 6, 2)->default(0);
            $table->decimal('present_days', 6, 2)->default(0);
            $table->decimal('paid_leave_days', 6, 2)->default(0);
            $table->decimal('unpaid_leave_days', 6, 2)->default(0);
            $table->decimal('absent_days', 6, 2)->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->timestamp('generated_at')->nullable();
        });

        Schema::table('salary_components', function (Blueprint $table): void {
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
        });

        Schema::create('payroll_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_record_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index('payroll_record_id');
        });
    }

    private function expandAnnouncements(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique();
            $table->text('summary')->nullable();
            $table->string('status')->default(AnnouncementStatus::Draft->value);
            $table->string('audience_type')->default(AnnouncementAudienceType::All->value);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['status', 'published_at']);
        });

        Schema::create('announcement_audiences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->string('audienceable_type');
            $table->unsignedBigInteger('audienceable_id');
            $table->timestamps();
            $table->unique(['announcement_id', 'audienceable_type', 'audienceable_id'], 'announcement_audience_unique');
            $table->index(['audienceable_type', 'audienceable_id']);
        });
    }

    private function expandAuditLogs(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('event_category')->nullable();
            $table->text('description')->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->text('user_agent')->nullable();
            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });
    }
};
