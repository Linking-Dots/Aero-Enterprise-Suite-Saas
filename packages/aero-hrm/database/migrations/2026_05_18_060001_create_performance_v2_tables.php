<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. hrm_review_templates
        Schema::create('hrm_review_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->json('sections');
            $table->json('rating_scale');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 2. hrm_review_cycles
        Schema::create('hrm_review_cycles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->foreignId('template_id')
                ->constrained('hrm_review_templates')
                ->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 16)->default('draft');
            $table->json('employee_ids')->nullable();
            $table->timestamps();
        });

        // 3. hrm_performance_reviews
        Schema::create('hrm_performance_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cycle_id')
                ->constrained('hrm_review_cycles')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->json('self_answers')->nullable();
            $table->json('manager_answers')->nullable();
            $table->decimal('final_rating', 4, 2)->nullable();
            $table->string('final_comment', 2000)->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['cycle_id', 'employee_id']);
        });

        // 4. hrm_goals
        Schema::create('hrm_goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('specific', 500);
            $table->string('measurable', 500);
            $table->string('achievable', 500)->nullable();
            $table->string('relevant', 500)->nullable();
            $table->date('time_bound');
            $table->tinyInteger('progress')->default(0);
            $table->string('status', 20)->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });

        // 5. hrm_feedback_360_requests
        Schema::create('hrm_feedback_360_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subject_employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->foreignId('requester_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->json('respondent_ids');
            $table->date('due_on');
            $table->string('status', 16)->default('open');
            $table->timestamps();
        });

        // 6. hrm_calibration_sessions
        Schema::create('hrm_calibration_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cycle_id')
                ->constrained('hrm_review_cycles')
                ->cascadeOnDelete();
            $table->string('name', 120);
            $table->json('grid')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_calibration_sessions');
        Schema::dropIfExists('hrm_feedback_360_requests');
        Schema::dropIfExists('hrm_goals');
        Schema::dropIfExists('hrm_performance_reviews');
        Schema::dropIfExists('hrm_review_cycles');
        Schema::dropIfExists('hrm_review_templates');
    }
};
