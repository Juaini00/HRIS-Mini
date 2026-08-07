<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\EmploymentType;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $location = Location::firstOrCreate(['name' => 'Kantor Makassar'], ['timezone' => 'Asia/Makassar']);
        $people = Department::firstOrCreate(['code' => 'HR'], ['name' => 'People & Culture']);
        $engineering = Department::firstOrCreate(['code' => 'ENG'], ['name' => 'Engineering']);
        $hrPosition = Position::firstOrCreate(['department_id' => $people->id, 'name' => 'HR Administrator']);
        $developer = Position::firstOrCreate(['department_id' => $engineering->id, 'name' => 'Software Engineer']);
        $leaveType = LeaveType::firstOrCreate(['name' => 'Cuti Tahunan'], ['annual_quota' => 12, 'is_paid' => true]);
        $permanent = EmploymentType::firstOrCreate(['name' => 'Permanent']);
        Setting::updateOrCreate(['key' => 'company_name'], ['value' => 'NusaHR', 'is_public' => true]);
        Setting::updateOrCreate(['key' => 'work_starts_at'], ['value' => '08:00']);
        Setting::updateOrCreate(['key' => 'work_ends_at'], ['value' => '17:00']);
        Setting::updateOrCreate(['key' => 'late_tolerance_minutes'], ['value' => '15']);
        Setting::updateOrCreate(['key' => 'currency'], ['value' => 'IDR', 'is_public' => true]);
        $transport = SalaryComponent::firstOrCreate(['name' => 'Transport Allowance'], ['type' => 'earning', 'calculation_type' => 'fixed', 'value' => 500000]);

        $accounts = [
            ['name' => 'NusaHR Super Admin', 'email' => 'admin@nusahr.test', 'role' => UserRole::SuperAdmin, 'number' => 'NSH-0001', 'department' => $people, 'position' => $hrPosition, 'salary' => 15000000],
            ['name' => 'NusaHR HR Admin', 'email' => 'hr@nusahr.test', 'role' => UserRole::HrAdmin, 'number' => 'NSH-0002', 'department' => $people, 'position' => $hrPosition, 'salary' => 10000000],
            ['name' => 'Andi Manager', 'email' => 'manager@nusahr.test', 'role' => UserRole::Manager, 'number' => 'NSH-0003', 'department' => $engineering, 'position' => $developer, 'salary' => 12000000],
            ['name' => 'Sari Employee', 'email' => 'employee@nusahr.test', 'role' => UserRole::Employee, 'number' => 'NSH-0004', 'department' => $engineering, 'position' => $developer, 'salary' => 8000000],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(['email' => $account['email']], ['name' => $account['name'], 'password' => Hash::make('NusaHR123!'), 'role' => $account['role'], 'is_active' => true, 'email_verified_at' => now()]);
            $employee = $user->employee()->updateOrCreate([], ['employee_number' => $account['number'], 'department_id' => $account['department']->id, 'position_id' => $account['position']->id, 'location_id' => $location->id, 'employment_type_id' => $permanent->id, 'joined_at' => now()->subYear(), 'basic_salary' => $account['salary']]);
            $employee->salaryHistories()->updateOrCreate(['effective_from' => now()->subYear()->toDateString()], ['amount' => $account['salary'], 'created_by' => $user->id, 'notes' => 'Demo initial salary']);
            $employee->salaryComponents()->syncWithoutDetaching([$transport->id => ['effective_from' => now()->subYear()->toDateString()]]);
            LeaveBalance::updateOrCreate(['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => now()->year], ['entitled' => 12, 'used' => 0, 'pending' => 0]);
        }

        $manager = User::where('email', 'manager@nusahr.test')->firstOrFail()->employee;
        User::where('email', 'employee@nusahr.test')->firstOrFail()->employee->update(['manager_id' => $manager->id]);

        Announcement::firstOrCreate(['title' => 'Selamat datang di NusaHR'], ['author_id' => User::where('email', 'admin@nusahr.test')->value('id'), 'body' => 'Portal HR terpadu untuk mengelola pekerjaan dan kebutuhan karyawan.', 'audience' => 'all', 'published_at' => now()]);
    }
}
