<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Payroll;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\PayComponent;
use Aero\HRM\Models\Payslip;
use Aero\HRM\Models\PayrollRun;
use Aero\HRM\Models\SalaryStructure;
use Aero\HRM\Services\Payroll\PayrollRunGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class PayrollRunGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Inertia\ServiceProvider::class,
            \Spatie\Activitylog\ActivitylogServiceProvider::class,
            \Aero\Auth\AeroAuthServiceProvider::class,
            AeroCoreServiceProvider::class,
            HRMACServiceProvider::class,
            AeroHrmServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('view.paths', [
            realpath(__DIR__.'/../../fixtures/views'),
        ]);
        // Disable Spatie activity logging — prevents writes to the activity_log
        // table which is not migrated in the test environment.
        $app['config']->set('activitylog.enabled', false);
        $app['config']->set('activitylog.default_auth_driver', null);
    }

    /**
     * Patch employee_salary_structures to add the user_id column that
     * Employee::salaryStructure() needs for its HasMany relationship.
     *
     * The legacy migration created the table with employee_id as the PK-link,
     * but the v2 PayrollRunGenerator reaches salary structures via
     * `employee->salaryStructure` which uses `user_id` as the FK key.
     */
    protected function afterRefreshingDatabase(): void
    {
        if (! Schema::hasTable('employee_salary_structures')) {
            return;
        }

        // Add user_id so Employee::salaryStructure() HasMany(FK='user_id') can resolve.
        if (! Schema::hasColumn('employee_salary_structures', 'user_id')) {
            Schema::table('employee_salary_structures', function ($table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            });
        }

        // Add salary_structure_id so the generator can read it alongside salary_component_id.
        if (! Schema::hasColumn('employee_salary_structures', 'salary_structure_id')) {
            Schema::table('employee_salary_structures', function ($table) {
                $table->unsignedBigInteger('salary_structure_id')->nullable();
            });
        }

        // Add deleted_at — EmployeeSalaryStructure uses SoftDeletes but the legacy
        // migration omitted the column.
        if (! Schema::hasColumn('employee_salary_structures', 'deleted_at')) {
            Schema::table('employee_salary_structures', function ($table) {
                $table->softDeletes();
            });
        }
    }

    // -------------------------------------------------------------------------
    // 1. PayrollRunGenerator creates payslips with correct totals
    // -------------------------------------------------------------------------

    public function test_run_creation_generates_payslips_with_correct_totals(): void
    {
        // Create an HRA pay component: earning, percent_of_basic = 20%
        $hra = PayComponent::factory()->create([
            'code'      => 'HRA',
            'name'      => 'House Rent Allowance',
            'kind'      => 'earning',
            'calc_type' => 'percent_of_basic',
            'value'     => 20.0,
            'active'    => true,
        ]);

        // Create a salary structure: basic = 1000, with the HRA component
        $structure = SalaryStructure::factory()->create([
            'basic'         => 1000.00,
            'component_ids' => [$hra->id],
            'active'        => true,
        ]);

        // Create 2 employees linked to the salary structure via the new direct FK
        $employees = Employee::factory()->count(2)->create([
            'salary_structure_id' => $structure->id,
        ]);

        foreach ($employees as $employee) {
            DB::table('employee_salary_structures')->insert([
                'employee_id'          => $employee->id,        // NOT NULL FK in migration
                'user_id'              => $employee->user_id,   // added by afterRefreshingDatabase for the relationship
                'salary_component_id'  => $structure->id,
                'salary_structure_id'  => $structure->id,       // added by afterRefreshingDatabase
                'is_active'            => 1,
                'effective_from'       => now()->subMonth()->toDateString(),
                'value'                => 0,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

        $employeeIds = $employees->pluck('id')->toArray();

        /** @var PayrollRunGenerator $generator */
        $generator = app(PayrollRunGenerator::class);

        $run = $generator->create([
            'label'        => 'May 2026',
            'period_start' => '2026-05-01',
            'period_end'   => '2026-05-31',
        ], $employeeIds);

        $run->loadMissing('payslips');

        $this->assertCount(2, $run->payslips, 'Expected 2 payslips — one per employee.');
        $this->assertGreaterThan(0, (float) $run->total_gross, 'total_gross must be positive.');
    }

    // -------------------------------------------------------------------------
    // 2. Payslip bank_account_number is encrypted at rest
    // -------------------------------------------------------------------------

    public function test_payslip_bank_account_number_is_encrypted_at_rest(): void
    {
        $user     = User::factory()->create(['email_verified_at' => now()]);
        $run      = PayrollRun::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        // Act as the user so Spatie's CauserResolver can resolve the auth guard.
        $this->actingAs($user);

        // Create the payslip with a known account number.
        $payslip = Payslip::factory()->create([
            'payroll_run_id'      => $run->id,
            'employee_id'         => $employee->id,
            'bank_account_number' => '1234567890',
        ]);

        // Raw DB value must be ciphertext — not the plaintext string.
        $raw = DB::table('hrm_payslips')
            ->where('id', $payslip->id)
            ->value('bank_account_number');

        $this->assertNotEquals(
            '1234567890',
            $raw,
            'bank_account_number must be stored as ciphertext, not plaintext.'
        );
        $this->assertNotNull($raw, 'Raw value should not be null — encryption must have occurred.');

        // Model accessor must decrypt transparently.
        $this->assertEquals(
            '1234567890',
            $payslip->fresh()->bank_account_number,
            'Model accessor must return the decrypted plaintext value.'
        );
    }
}
