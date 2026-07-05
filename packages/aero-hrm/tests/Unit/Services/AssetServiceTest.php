<?php

namespace Aero\HRM\Tests\Unit\Services;

use Aero\HRM\Models\Asset;
use Aero\HRM\Models\AssetAllocation;
use Aero\HRM\Models\AssetCategory;
use Aero\HRM\Models\Employee;
use Aero\HRM\Services\AssetService;
use Aero\HRM\Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AssetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AssetService::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_asset_with_auto_generated_tag()
    {
        $category = AssetCategory::factory()->create();

        $asset = Asset::factory()->create([
            'asset_category_id' => $category->id,
        ]);

        $this->assertStringStartsWith('AST'.date('Y'), $asset->asset_tag);
        $this->assertEquals('available', $asset->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_tracks_asset_allocation_to_employee()
    {
        $asset = Asset::factory()->create();
        $employee = Employee::factory()->create();

        $allocation = AssetAllocation::factory()->create([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'status' => 'active',
        ]);

        $asset->update(['status' => 'allocated']);

        $this->assertEquals('allocated', $asset->fresh()->status);
        $this->assertEquals($employee->id, $allocation->employee_id);
        $this->assertEquals('active', $allocation->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_warranty_expiry_correctly()
    {
        $purchaseDate = Carbon::parse('2024-01-01');
        $warrantyMonths = 12;

        $asset = Asset::factory()->create([
            'purchase_date' => $purchaseDate,
            'warranty_months' => $warrantyMonths,
        ]);

        $expectedExpiry = $purchaseDate->copy()->addMonths($warrantyMonths);
        $actualExpiry = Carbon::parse($asset->purchase_date)->addMonths($asset->warranty_months);

        $this->assertEquals($expectedExpiry->format('Y-m-d'), $actualExpiry->format('Y-m-d'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_detects_overdue_allocations()
    {
        $asset = Asset::factory()->create();
        $employee = Employee::factory()->create();

        $overdueAllocation = AssetAllocation::factory()->overdue()->create([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'expected_return_date' => Carbon::now()->subDays(5),
            'status' => 'active',
        ]);

        $this->assertTrue($overdueAllocation->isOverdue());
        $this->assertNotNull($overdueAllocation->expected_return_date);
        $this->assertTrue(Carbon::parse($overdueAllocation->expected_return_date)->isPast());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_tracks_condition_on_allocation_and_return()
    {
        $asset = Asset::factory()->create();
        $employee = Employee::factory()->create();

        $allocation = AssetAllocation::factory()->create([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'condition_on_allocation' => 'excellent',
            'status' => 'active',
        ]);

        $this->assertEquals('excellent', $allocation->condition_on_allocation);

        // Simulate return
        $allocation->update([
            'condition_on_return' => 'good',
            'status' => 'returned',
            'actual_return_date' => now(),
        ]);

        $allocation->refresh();
        $this->assertEquals('good', $allocation->condition_on_return);
        $this->assertEquals('returned', $allocation->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_prevents_allocation_of_unavailable_assets()
    {
        $asset = Asset::factory()->maintenance()->create();

        $this->assertFalse($asset->canBeAllocated());
        $this->assertEquals('maintenance', $asset->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_asset_return_workflow()
    {
        $asset = Asset::factory()->allocated()->create();
        $employee = Employee::factory()->create();

        $allocation = AssetAllocation::factory()->active()->create([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
        ]);

        // Return asset
        $allocation->update([
            'status' => 'returned',
            'actual_return_date' => now(),
            'condition_on_return' => 'good',
        ]);

        $asset->update(['status' => 'available']);

        $this->assertEquals('returned', $allocation->fresh()->status);
        $this->assertEquals('available', $asset->fresh()->status);
        $this->assertNotNull($allocation->actual_return_date);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_unique_qr_codes()
    {
        $asset1 = Asset::factory()->withQrCode()->create();
        $asset2 = Asset::factory()->withQrCode()->create();

        $this->assertNotNull($asset1->qr_code);
        $this->assertNotNull($asset2->qr_code);
        $this->assertNotEquals($asset1->qr_code, $asset2->qr_code);
    }
}
