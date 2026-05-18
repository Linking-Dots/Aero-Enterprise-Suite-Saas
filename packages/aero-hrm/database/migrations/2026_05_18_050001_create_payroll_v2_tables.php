<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll v2 — salary structures, pay components, tax brackets, payroll runs, payslips.
 *
 * These tables replace/augment the legacy payroll_* tables with a fully structured
 * pay-run workflow: define components → assign to salary structures → run payroll
 * → generate payslips with encrypted bank PII.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── hrm_pay_components ────────────────────────────────────────────────
        Schema::create('hrm_pay_components', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->enum('kind', ['earning', 'deduction']);
            $table->enum('calc_type', ['fixed', 'percent_of_basic', 'percent_of_gross', 'formula']);
            $table->decimal('value', 12, 4)->default(0);
            $table->boolean('taxable')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // ── hrm_salary_structures ─────────────────────────────────────────────
        Schema::create('hrm_salary_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->decimal('basic', 12, 2);
            $table->json('component_ids')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // ── hrm_tax_brackets ─────────────────────────────────────────────────
        Schema::create('hrm_tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 5)->default('US');
            $table->decimal('income_from', 14, 2);
            $table->decimal('income_to', 14, 2)->nullable();
            $table->decimal('rate', 5, 4);
            $table->integer('effective_year');
            $table->timestamps();
            $table->index(['country_code', 'effective_year']);
        });

        // ── hrm_payroll_runs ─────────────────────────────────────────────────
        Schema::create('hrm_payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100);
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'review', 'approved'])->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->timestamps();
            $table->index('status');
        });

        // ── hrm_payslips ─────────────────────────────────────────────────────
        Schema::create('hrm_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')
                ->constrained('hrm_payroll_runs')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->decimal('gross', 14, 2);
            $table->decimal('tax', 14, 2);
            $table->decimal('deductions_total', 14, 2)->default(0);
            $table->decimal('net', 14, 2);
            $table->json('line_items');
            $table->json('employee_snapshot');
            $table->text('bank_account_number')->nullable();
            $table->text('bank_name')->nullable();
            $table->text('bank_routing_number')->nullable();
            $table->timestamps();
            $table->unique(['payroll_run_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_payslips');
        Schema::dropIfExists('hrm_payroll_runs');
        Schema::dropIfExists('hrm_tax_brackets');
        Schema::dropIfExists('hrm_salary_structures');
        Schema::dropIfExists('hrm_pay_components');
    }
};
