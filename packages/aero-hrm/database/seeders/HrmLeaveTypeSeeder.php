<?php

namespace Aero\HRM\Database\Seeders;

use Aero\HRM\Models\LeaveType;
use Illuminate\Database\Seeder;

/**
 * Seeds the standard set of leave types into the `leave_types` table.
 *
 * Columns align 1:1 with the LeaveType model / create_leave_types_table migration:
 * name, code, color, days_per_year, is_paid, requires_approval, carry_forward,
 * encashable, max_carry_forward, is_active.
 */
class HrmLeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Annual Leave', 'code' => 'AL', 'color' => '#3B82F6',
                'days_per_year' => 21, 'is_paid' => true, 'requires_approval' => true,
                'carry_forward' => true, 'encashable' => true, 'max_carry_forward' => 10,
            ],
            [
                'name' => 'Sick Leave', 'code' => 'SL', 'color' => '#EF4444',
                'days_per_year' => 15, 'is_paid' => true, 'requires_approval' => true,
                'carry_forward' => false, 'encashable' => false, 'max_carry_forward' => null,
            ],
            [
                'name' => 'Casual Leave', 'code' => 'CL', 'color' => '#F59E0B',
                'days_per_year' => 12, 'is_paid' => true, 'requires_approval' => true,
                'carry_forward' => false, 'encashable' => false, 'max_carry_forward' => null,
            ],
            [
                'name' => 'Maternity Leave', 'code' => 'ML', 'color' => '#EC4899',
                'days_per_year' => 90, 'is_paid' => true, 'requires_approval' => true,
                'carry_forward' => false, 'encashable' => false, 'max_carry_forward' => null,
            ],
            [
                'name' => 'Paternity Leave', 'code' => 'PL', 'color' => '#8B5CF6',
                'days_per_year' => 14, 'is_paid' => true, 'requires_approval' => true,
                'carry_forward' => false, 'encashable' => false, 'max_carry_forward' => null,
            ],
            [
                'name' => 'Bereavement Leave', 'code' => 'BL', 'color' => '#6B7280',
                'days_per_year' => 5, 'is_paid' => true, 'requires_approval' => true,
                'carry_forward' => false, 'encashable' => false, 'max_carry_forward' => null,
            ],
            [
                'name' => 'Marriage Leave', 'code' => 'MRL', 'color' => '#10B981',
                'days_per_year' => 3, 'is_paid' => true, 'requires_approval' => true,
                'carry_forward' => false, 'encashable' => false, 'max_carry_forward' => null,
            ],
            [
                'name' => 'Unpaid Leave', 'code' => 'UL', 'color' => '#9CA3AF',
                'days_per_year' => 30, 'is_paid' => false, 'requires_approval' => true,
                'carry_forward' => false, 'encashable' => false, 'max_carry_forward' => null,
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::firstOrCreate(
                ['code' => $leaveType['code']],
                array_merge($leaveType, ['is_active' => true])
            );
        }
    }
}
