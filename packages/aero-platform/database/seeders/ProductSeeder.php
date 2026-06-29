<?php

namespace Aero\Platform\Database\Seeders;

use Aero\Platform\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    private array $products = [
        [
            'code'          => 'hrm',
            'module_code'   => 'hrm',
            'name'          => 'HRM Suite',
            'description'   => 'Complete human resource management — employees, attendance, leave, payroll, performance.',
            'icon'          => 'UserGroupIcon',
            'monthly_price' => 29.00,
            'yearly_price'  => 290.00,
            'sort_order'    => 1,
        ],
        [
            'code'          => 'crm',
            'module_code'   => 'crm',
            'name'          => 'CRM Suite',
            'description'   => 'Customer relationship management — leads, contacts, pipeline, deals.',
            'icon'          => 'BuildingOfficeIcon',
            'monthly_price' => 39.00,
            'yearly_price'  => 390.00,
            'sort_order'    => 2,
        ],
        [
            'code'          => 'project',
            'module_code'   => 'project',
            'name'          => 'Project Management',
            'description'   => 'Projects, tasks, milestones, Gantt charts, time tracking.',
            'icon'          => 'RectangleStackIcon',
            'monthly_price' => 24.00,
            'yearly_price'  => 240.00,
            'sort_order'    => 3,
        ],
        [
            'code'          => 'compliance',
            'module_code'   => 'compliance',
            'name'          => 'Compliance',
            'description'   => 'Regulatory compliance tracking, audit trails, and compliance reporting.',
            'icon'          => 'ShieldCheckIcon',
            'monthly_price' => 34.00,
            'yearly_price'  => 340.00,
            'sort_order'    => 4,
        ],
        [
            'code'          => 'quality',
            'module_code'   => 'quality',
            'name'          => 'Quality Management & Labs',
            'description'   => 'Full QMS: inspections (ITP/WIR), material labs (LIMS), NCR/CAPA, SPC, calibration, supplier quality, audits, ISO compliance, and EAM asset-quality checks.',
            'icon'          => 'BeakerIcon',
            'monthly_price' => 34.00,
            'yearly_price'  => 340.00,
            'sort_order'    => 5,
        ],
        [
            'code'          => 'rfi',
            'module_code'   => 'rfi',
            'name'          => 'RFI & Site Intelligence',
            'description'   => 'Request For Inspection (construction) + RFI/RFQ workflow, geo-fenced validation, and linear chainage mapping.',
            'icon'          => 'MapPinIcon',
            'monthly_price' => 29.00,
            'yearly_price'  => 290.00,
            'sort_order'    => 6,
        ],
    ];

    public function run(): void
    {
        // Gate the catalog to installed, priced product packages: module_pricing is
        // populated (by PlanSeedingStep / ModulePricingSeeder) only for product
        // packages present in this deployment. Seeding a product whose package isn't
        // installed would list a product in the marketplace that can't be provisioned.
        $installed = Schema::hasTable('module_pricing')
            ? DB::table('module_pricing')->where('is_active', true)->pluck('module_code')->all()
            : null;

        $seeded = 0;
        foreach ($this->products as $data) {
            if ($installed !== null && ! in_array($data['module_code'], $installed, true)) {
                continue;
            }

            Product::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, [
                    'id'                    => (string) Str::uuid(),
                    'currency'              => 'USD',
                    'is_active'             => true,
                    'is_marketplace_visible' => true,
                ])
            );
            $seeded++;
        }

        $this->command?->info("Products seeded: {$seeded}");
    }
}
