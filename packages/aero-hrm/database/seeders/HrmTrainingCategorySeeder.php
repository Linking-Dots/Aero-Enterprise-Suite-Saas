<?php

namespace Aero\HRM\Database\Seeders;

use Aero\HRM\Models\TrainingCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HrmTrainingCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Technical Skills', 'description' => 'Programming, tools, and technology-related training.'],
            ['name' => 'Soft Skills', 'description' => 'Communication, teamwork, and interpersonal skills development.'],
            ['name' => 'Compliance & Regulatory', 'description' => 'Legal compliance, industry regulations, and policy training.'],
            ['name' => 'Leadership & Management', 'description' => 'Management techniques, strategic thinking, and leadership development.'],
            ['name' => 'Health & Safety', 'description' => 'Workplace safety, emergency procedures, and health awareness.'],
            ['name' => 'Product Knowledge', 'description' => 'Training on company products, services, and solutions.'],
            ['name' => 'Onboarding', 'description' => 'New employee orientation and induction programs.'],
            ['name' => 'Professional Development', 'description' => 'Career growth, certifications, and continuous learning.'],
        ];

        foreach ($categories as $category) {
            TrainingCategory::firstOrCreate(
                ['name' => $category['name']],
                array_merge($category, [
                    'slug' => Str::slug($category['name']),
                    'is_active' => true,
                ])
            );
        }
    }
}
