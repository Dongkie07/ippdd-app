<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Budget;
use App\Models\PerformanceIndicator;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Departments
        $departments = [
            ['name' => 'Institute of Computing', 'code' => 'BSIT'],
            ['name' => 'Institute of Education', 'code' => 'BSED'],
            ['name' => 'Institute of Leadership', 'code' => 'BPA'],
            ['name' => 'Institute of Aquatic and Applied Sciences', 'code' => 'BSMB'],
        ];

        foreach ($departments as $deptData) {
            $department = Department::create($deptData);

            // 2. Create Budgets for 2024, 2025, 2026
            $years = [2024, 2025, 2026];
            foreach ($years as $year) {
                // Randomly increase budget each year
                $baseBudget = rand(1000000, 5000000); 
                
                Budget::create([
                    'department_id' => $department->id,
                    'year' => $year,
                    'allocated_amount' => $baseBudget + ($year == 2026 ? 1000000 : 0),
                    'spent_amount' => $baseBudget * (rand(70, 95) / 100), // 70-95% spent
                ]);

                // 3. Create Performance Indicators
                PerformanceIndicator::create([
                    'department_id' => $department->id,
                    'year' => $year,
                    'target_count' => rand(10, 50),
                    'achieved_count' => rand(5, 45),
                ]);
            }
        }
    }
}