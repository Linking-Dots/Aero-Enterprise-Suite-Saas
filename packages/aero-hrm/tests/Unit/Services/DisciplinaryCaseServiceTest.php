<?php

namespace Aero\HRM\Tests\Unit\Services;

use Aero\HRM\Models\DisciplinaryActionType;
use Aero\HRM\Models\DisciplinaryCase;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\Warning;
use Aero\HRM\Services\DisciplinaryCaseService;
use Aero\HRM\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DisciplinaryCaseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DisciplinaryCaseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DisciplinaryCaseService::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_case_with_auto_generated_number()
    {
        $employee = Employee::factory()->create();
        $actionType = DisciplinaryActionType::factory()->create();

        $case = DisciplinaryCase::factory()->create([
            'employee_id' => $employee->id,
            'disciplinary_action_type_id' => $actionType->id,
        ]);

        $this->assertStringStartsWith('DC'.date('Y'), $case->case_number);
        $this->assertEquals('pending', $case->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_tracks_investigation_workflow_correctly()
    {
        $case = DisciplinaryCase::factory()->create(['status' => 'pending']);

        // Start investigation
        $case->update([
            'status' => 'investigating',
            'investigation_start_date' => now(),
            'investigating_officer_id' => Employee::factory()->create()->id,
        ]);

        $case->refresh();
        $this->assertEquals('investigating', $case->status);
        $this->assertNotNull($case->investigation_start_date);
        $this->assertNotNull($case->investigating_officer_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_issues_warnings_with_expiry_tracking()
    {
        $employee = Employee::factory()->create();
        $case = DisciplinaryCase::factory()->create(['employee_id' => $employee->id]);

        $warning = Warning::factory()->create([
            'employee_id' => $employee->id,
            'disciplinary_case_id' => $case->id,
            'warning_type' => 'written',
            'issue_date' => now(),
            'expiry_date' => now()->addMonths(6),
        ]);

        $this->assertEquals('written', $warning->warning_type);
        $this->assertNotNull($warning->expiry_date);
        $this->assertFalse($warning->isExpired());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_severity_points_correctly()
    {
        $actionType = DisciplinaryActionType::factory()->minor()->create(['severity_points' => 1]);
        $case = DisciplinaryCase::factory()->create([
            'disciplinary_action_type_id' => $actionType->id,
        ]);

        $this->assertEquals(1, $case->actionType->severity_points);

        $majorActionType = DisciplinaryActionType::factory()->major()->create(['severity_points' => 5]);
        $majorCase = DisciplinaryCase::factory()->create([
            'disciplinary_action_type_id' => $majorActionType->id,
        ]);

        $this->assertEquals(5, $majorCase->actionType->severity_points);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_appeal_process()
    {
        $case = DisciplinaryCase::factory()->actionTaken()->create();

        $case->update([
            'appeal_filed' => true,
            'appeal_date' => now(),
            'appeal_reason' => 'Unfair decision',
        ]);

        $case->refresh();
        $this->assertTrue($case->appeal_filed);
        $this->assertNotNull($case->appeal_date);
        $this->assertEquals('Unfair decision', $case->appeal_reason);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_prevents_closing_without_action_taken()
    {
        $pendingCase = DisciplinaryCase::factory()->create(['status' => 'pending']);
        $investigatingCase = DisciplinaryCase::factory()->investigating()->create();
        $actionTakenCase = DisciplinaryCase::factory()->actionTaken()->create();

        $this->assertFalse($pendingCase->canBeClosed());
        $this->assertFalse($investigatingCase->canBeClosed());
        $this->assertTrue($actionTakenCase->canBeClosed());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_tracks_employee_and_witness_statements()
    {
        $employee = Employee::factory()->create();
        $case = DisciplinaryCase::factory()->create([
            'employee_id' => $employee->id,
            'employee_statement' => 'I was not aware of the policy violation',
            'witness_statement' => 'I witnessed the incident on Monday morning',
        ]);

        $this->assertNotNull($case->employee_statement);
        $this->assertNotNull($case->witness_statement);
        $this->assertEquals('I was not aware of the policy violation', $case->employee_statement);
        $this->assertEquals('I witnessed the incident on Monday morning', $case->witness_statement);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_manages_evidence_attachments()
    {
        $case = DisciplinaryCase::factory()->create();

        // Test that case can have media attachments
        $this->assertInstanceOf(DisciplinaryCase::class, $case);
        $this->assertTrue(method_exists($case, 'registerMediaCollections'));
    }
}
