<?php

namespace Aero\HRM\Database\Seeders;

use Illuminate\Database\Seeder;

class HrmDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HrmDepartmentSeeder::class,
            HrmDesignationSeeder::class,
            HrmLeaveTypeSeeder::class,
            HrmHolidaySeeder::class,
            HrmExpenseCategorySeeder::class,
            HrmAssetCategorySeeder::class,
            HrmTrainingCategorySeeder::class,
            HrmGrievanceCategorySeeder::class,
            HrmDisciplinaryActionTypeSeeder::class,
            HrmShiftScheduleSeeder::class,
            HrmSkillSeeder::class,
        ]);
    }
}
