<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_pulse_surveys', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description')->nullable();
            $t->json('questions'); // [{id, type, text, options?}]
            $t->json('audience_filter')->nullable();
            $t->string('status', 16)->default('draft'); // draft, active, closed
            $t->timestamp('opens_at')->nullable();
            $t->timestamp('closes_at')->nullable();
            $t->boolean('anonymous')->default(true);
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->index(['status', 'closes_at']);
        });

        Schema::create('hrm_pulse_responses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('survey_id')->constrained('hrm_pulse_surveys')->cascadeOnDelete();
            $t->string('respondent_hash', 64);
            $t->json('answers'); // [{question_id, value}]
            $t->json('demographics_snapshot')->nullable();
            $t->timestamp('submitted_at');
            $t->unique(['survey_id', 'respondent_hash']);
        });

        Schema::create('hrm_workforce_plans', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('fiscal_year');
            $t->foreignId('department_id')->constrained('departments');
            $t->unsignedInteger('target_headcount');
            $t->unsignedInteger('target_hires')->default(0);
            $t->unsignedInteger('target_attrition')->default(0);
            $t->text('notes')->nullable();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['fiscal_year', 'department_id']);
        });

        Schema::create('hrm_attrition_risk_scores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->decimal('score', 5, 4);
            $t->string('band', 8); // low, medium, high, critical
            $t->json('factors');
            $t->timestamp('computed_at');
            $t->unique('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_attrition_risk_scores');
        Schema::dropIfExists('hrm_workforce_plans');
        Schema::dropIfExists('hrm_pulse_responses');
        Schema::dropIfExists('hrm_pulse_surveys');
    }
};
