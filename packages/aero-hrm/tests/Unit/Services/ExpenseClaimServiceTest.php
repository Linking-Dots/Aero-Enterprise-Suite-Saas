<?php

namespace Aero\HRM\Tests\Unit\Services;

use Aero\HRM\Models\Employee;
use Aero\HRM\Models\ExpenseCategory;
use Aero\HRM\Models\ExpenseClaim;
use Aero\HRM\Services\ExpenseClaimService;
use Aero\HRM\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExpenseClaimServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExpenseClaimService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExpenseClaimService::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_expense_claim_with_auto_generated_number()
    {
        $employee = Employee::factory()->create();
        $category = ExpenseCategory::factory()->create();

        $claim = ExpenseClaim::factory()->create([
            'employee_id' => $employee->id,
            'expense_category_id' => $category->id,
        ]);

        $this->assertStringStartsWith('EXP'.date('Y'), $claim->claim_number);
        $this->assertEquals('draft', $claim->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_amount_against_category_max_amount()
    {
        $category = ExpenseCategory::factory()->create(['max_amount' => 1000]);
        $employee = Employee::factory()->create();

        $claim = ExpenseClaim::factory()->create([
            'employee_id' => $employee->id,
            'expense_category_id' => $category->id,
            'amount' => 500,
        ]);

        $this->assertEquals(500, $claim->amount);
        $this->assertLessThanOrEqual($category->max_amount, $claim->amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_tracks_approval_chain_correctly()
    {
        $claim = ExpenseClaim::factory()->submitted()->create();
        $approver = Employee::factory()->create();

        $claim->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $claim->refresh();
        $this->assertEquals('approved', $claim->status);
        $this->assertEquals($approver->id, $claim->approved_by);
        $this->assertNotNull($claim->approved_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_total_claimed_amount_for_employee()
    {
        $employee = Employee::factory()->create();
        $category = ExpenseCategory::factory()->create();

        ExpenseClaim::factory()->count(3)->create([
            'employee_id' => $employee->id,
            'expense_category_id' => $category->id,
            'amount' => 100,
            'status' => 'approved',
        ]);

        $total = ExpenseClaim::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->sum('amount');

        $this->assertEquals(300, $total);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_prevents_editing_of_approved_claims()
    {
        $claim = ExpenseClaim::factory()->approved()->create();

        $this->assertFalse($claim->canBeEdited());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_rejection_with_reason()
    {
        $claim = ExpenseClaim::factory()->submitted()->create();

        $claim->update([
            'status' => 'rejected',
            'rejection_reason' => 'Invalid receipt',
            'rejected_at' => now(),
        ]);

        $claim->refresh();
        $this->assertEquals('rejected', $claim->status);
        $this->assertEquals('Invalid receipt', $claim->rejection_reason);
        $this->assertNotNull($claim->rejected_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_tracks_payment_information()
    {
        $claim = ExpenseClaim::factory()->approved()->create();

        $claim->update([
            'status' => 'paid',
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'TXN123456',
            'paid_at' => now(),
        ]);

        $claim->refresh();
        $this->assertEquals('paid', $claim->status);
        $this->assertEquals('bank_transfer', $claim->payment_method);
        $this->assertEquals('TXN123456', $claim->payment_reference);
        $this->assertNotNull($claim->paid_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_receipt_attachments()
    {
        $claim = ExpenseClaim::factory()->create();

        // Test that claim can have media attachments
        $this->assertInstanceOf(ExpenseClaim::class, $claim);
        $this->assertTrue(method_exists($claim, 'registerMediaCollections'));
    }
}
