<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hrm_benefit_enrollments');
        Schema::dropIfExists('hrm_benefit_enrollment_period_benefits');
        Schema::dropIfExists('hrm_benefit_enrollment_periods');
        Schema::dropIfExists('hrm_benefits');

        Schema::create('hrm_benefits', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->enum('category', ['health', 'dental', 'vision', 'life', 'disability', 'pension', 'wellness', 'other']);
            $t->text('description')->nullable();
            $t->string('provider')->nullable();
            $t->decimal('employee_cost', 12, 2)->default(0);
            $t->decimal('employer_cost', 12, 2)->default(0);
            $t->enum('frequency', ['monthly', 'biweekly', 'weekly', 'annual'])->default('monthly');
            $t->boolean('allows_dependents')->default(false);
            $t->decimal('dependent_cost', 12, 2)->nullable();
            $t->json('eligibility_rules')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('hrm_benefit_enrollment_periods', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->date('starts_at');
            $t->date('ends_at');
            $t->date('coverage_starts_at');
            $t->date('coverage_ends_at');
            $t->json('audience_filter')->nullable();
            $t->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $t->foreignId('created_by')->constrained('users');
            $t->timestamp('activated_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'starts_at', 'ends_at']);
        });

        Schema::create('hrm_benefit_enrollment_period_benefits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('period_id')->constrained('hrm_benefit_enrollment_periods')->cascadeOnDelete();
            $t->foreignId('benefit_id')->constrained('hrm_benefits')->cascadeOnDelete();
            $t->boolean('required')->default(false);
            // Explicit short name — the auto-generated name exceeds MySQL's 64-char limit.
            $t->unique(['period_id', 'benefit_id'], 'hrm_benefit_period_benefit_unique');
        });

        Schema::create('hrm_benefit_enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('period_id')->constrained('hrm_benefit_enrollment_periods');
            $t->foreignId('benefit_id')->constrained('hrm_benefits');
            $t->enum('status', ['enrolled', 'waived'])->default('enrolled');
            $t->unsignedTinyInteger('dependents_count')->default(0);
            $t->decimal('employee_cost_snapshot', 12, 2);
            $t->decimal('employer_cost_snapshot', 12, 2);
            $t->text('waiver_reason')->nullable();
            $t->timestamp('elected_at');
            $t->timestamps();
            $t->unique(['employee_id', 'period_id', 'benefit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_benefit_enrollments');
        Schema::dropIfExists('hrm_benefit_enrollment_period_benefits');
        Schema::dropIfExists('hrm_benefit_enrollment_periods');
        Schema::dropIfExists('hrm_benefits');
    }
};
