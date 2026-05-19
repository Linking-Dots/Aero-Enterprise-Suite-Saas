<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hrm_grievance_events');
        Schema::dropIfExists('hrm_grievances');
        Schema::dropIfExists('hrm_exit_interviews');
        Schema::dropIfExists('hrm_disciplinary_case_documents');
        Schema::dropIfExists('hrm_disciplinary_case_events');
        Schema::dropIfExists('hrm_warnings');
        Schema::dropIfExists('hrm_disciplinary_cases');
        Schema::dropIfExists('hrm_disciplinary_action_types');

        Schema::create('hrm_disciplinary_action_types', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $t->text('description')->nullable();
            $t->unsignedTinyInteger('escalates_after_count')->nullable();
            $t->string('escalates_to_type')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('hrm_disciplinary_cases', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('employee_id')->constrained('employees');
            $t->foreignId('action_type_id')->constrained('hrm_disciplinary_action_types');
            $t->foreignId('opened_by')->constrained('users');
            $t->date('incident_date');
            $t->string('subject');
            $t->text('description');
            $t->enum('status', ['open', 'awaiting_response', 'under_review', 'closed'])->default('open');
            $t->enum('outcome', ['none', 'verbal', 'written', 'pip', 'suspension', 'termination'])->default('none');
            $t->text('closure_notes')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->foreignId('closed_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->index(['status', 'employee_id']);
        });

        Schema::create('hrm_disciplinary_case_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('case_id')->constrained('hrm_disciplinary_cases')->cascadeOnDelete();
            $t->string('type');
            $t->json('payload')->nullable();
            $t->foreignId('actor_id')->nullable()->constrained('users');
            $t->timestamp('occurred_at');
        });

        Schema::create('hrm_disciplinary_case_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('case_id')->constrained('hrm_disciplinary_cases')->cascadeOnDelete();
            $t->string('disk');
            $t->string('path');
            $t->string('original_name');
            $t->unsignedInteger('size_bytes');
            $t->foreignId('uploaded_by')->constrained('users');
            $t->timestamps();
        });

        Schema::create('hrm_warnings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees');
            $t->foreignId('issued_by')->constrained('users');
            $t->foreignId('action_type_id')->nullable()->constrained('hrm_disciplinary_action_types');
            $t->string('subject');
            $t->text('body');
            $t->enum('status', ['issued', 'acknowledged', 'escalated'])->default('issued');
            $t->timestamp('issued_at');
            $t->timestamp('acknowledged_at')->nullable();
            $t->text('employee_response')->nullable();
            $t->foreignId('escalated_to_case_id')->nullable()->constrained('hrm_disciplinary_cases');
            $t->timestamps();
        });

        Schema::create('hrm_exit_interviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees');
            $t->date('scheduled_for');
            $t->foreignId('interviewer_id')->nullable()->constrained('users');
            $t->enum('status', ['scheduled', 'completed', 'no_show'])->default('scheduled');
            $t->json('responses')->nullable();
            $t->text('summary')->nullable();
            $t->unsignedTinyInteger('eligible_for_rehire')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_grievances', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('filed_by')->constrained('employees');
            $t->foreignId('against_employee_id')->nullable()->constrained('employees');
            $t->enum('category', ['harassment', 'discrimination', 'workplace_safety', 'policy_violation', 'interpersonal', 'other']);
            $t->string('subject');
            $t->text('description');
            $t->enum('confidentiality', ['standard', 'confidential', 'anonymous'])->default('standard');
            $t->enum('status', ['filed', 'under_investigation', 'resolved', 'dismissed'])->default('filed');
            $t->foreignId('investigator_id')->nullable()->constrained('users');
            $t->text('resolution_notes')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_grievance_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('grievance_id')->constrained('hrm_grievances')->cascadeOnDelete();
            $t->string('type');
            $t->json('payload')->nullable();
            $t->foreignId('actor_id')->nullable()->constrained('users');
            $t->timestamp('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_grievance_events');
        Schema::dropIfExists('hrm_grievances');
        Schema::dropIfExists('hrm_exit_interviews');
        Schema::dropIfExists('hrm_disciplinary_case_documents');
        Schema::dropIfExists('hrm_disciplinary_case_events');
        Schema::dropIfExists('hrm_warnings');
        Schema::dropIfExists('hrm_disciplinary_cases');
        Schema::dropIfExists('hrm_disciplinary_action_types');
    }
};
