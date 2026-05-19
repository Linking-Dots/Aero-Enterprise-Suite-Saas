<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hrm_safety_training_assignments');
        Schema::dropIfExists('hrm_safety_trainings');
        Schema::dropIfExists('hrm_safety_inspection_findings');
        Schema::dropIfExists('hrm_safety_inspections');
        Schema::dropIfExists('hrm_safety_incident_investigations');
        Schema::dropIfExists('hrm_safety_incidents');

        Schema::create('hrm_safety_incidents', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->dateTime('occurred_at');
            $t->string('type', 64);          // injury, near_miss, property_damage
            $t->string('severity', 32);      // low, medium, high, critical
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->string('location')->nullable();
            $t->text('description');
            $t->json('injured_person_ids')->nullable();
            $t->string('status', 24)->default('reported'); // reported, investigating, closed
            $t->foreignId('reported_by')->constrained('users');
            $t->foreignId('closed_by')->nullable()->constrained('users');
            $t->dateTime('closed_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'severity']);
        });

        Schema::create('hrm_safety_incident_investigations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('incident_id')->constrained('hrm_safety_incidents')->cascadeOnDelete();
            $t->foreignId('investigator_id')->constrained('users');
            $t->text('root_cause');
            $t->text('corrective_action');
            $t->timestamps();
        });

        Schema::create('hrm_safety_inspections', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->string('title');
            $t->date('scheduled_date');
            $t->date('conducted_date')->nullable();
            $t->foreignId('inspector_id')->constrained('users');
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->string('location')->nullable();
            $t->enum('status', ['scheduled', 'in_progress', 'completed'])->default('scheduled');
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_safety_inspection_findings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inspection_id')->constrained('hrm_safety_inspections')->cascadeOnDelete();
            $t->string('category');
            $t->text('description');
            $t->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $t->enum('status', ['open', 'resolved'])->default('open');
            $t->date('due_date')->nullable();
            $t->text('resolution_notes')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_safety_trainings', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('type');                  // induction, refresher, equipment, emergency
            $t->integer('duration_minutes')->default(60);
            $t->boolean('mandatory')->default(false);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('hrm_safety_training_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('training_id')->constrained('hrm_safety_trainings')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('assigned_by')->constrained('users');
            $t->date('due_date')->nullable();
            $t->enum('status', ['assigned', 'completed'])->default('assigned');
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['training_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_safety_training_assignments');
        Schema::dropIfExists('hrm_safety_trainings');
        Schema::dropIfExists('hrm_safety_inspection_findings');
        Schema::dropIfExists('hrm_safety_inspections');
        Schema::dropIfExists('hrm_safety_incident_investigations');
        Schema::dropIfExists('hrm_safety_incidents');
    }
};
