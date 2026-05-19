<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hrm_talent_mobility_postings');
        Schema::dropIfExists('hrm_succession_candidates');
        Schema::dropIfExists('hrm_talent_pool_members');
        Schema::dropIfExists('hrm_career_path_employees');
        Schema::dropIfExists('hrm_career_milestones');
        Schema::dropIfExists('hrm_career_paths');

        Schema::create('hrm_career_paths', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug', 120)->unique();
            $t->text('description')->nullable();
            $t->foreignId('target_role_id')->nullable()->constrained('designations')->nullOnDelete();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('hrm_career_milestones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('career_path_id')->constrained('hrm_career_paths')->cascadeOnDelete();
            $t->string('name');
            $t->integer('order_index')->default(0);
            $t->json('requirements')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_career_path_employees', function (Blueprint $t) {
            $t->id();
            $t->foreignId('hrm_career_path_id')->constrained('hrm_career_paths')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('current_milestone_id')->nullable()->constrained('hrm_career_milestones')->nullOnDelete();
            $t->timestamp('assigned_at');
            $t->timestamps();
            $t->unique(['hrm_career_path_id', 'employee_id']);
        });

        Schema::create('hrm_talent_pool_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->enum('tier', ['high_potential', 'key_talent', 'emerging'])->default('emerging');
            $t->text('notes')->nullable();
            $t->foreignId('added_by')->constrained('users');
            $t->timestamps();
            $t->unique('employee_id');
        });

        Schema::create('hrm_succession_candidates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('role_id')->constrained('designations')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->enum('readiness', ['ready_now', '1_2_years', '3_5_years']);
            $t->text('notes')->nullable();
            $t->foreignId('nominated_by')->constrained('users');
            $t->timestamps();
            $t->unique(['role_id', 'employee_id']);
        });

        Schema::create('hrm_talent_mobility_postings', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description')->nullable();
            $t->enum('type', ['transfer', 'project', 'secondment', 'promotion'])->default('transfer');
            $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $t->foreignId('role_id')->nullable()->constrained('designations')->nullOnDelete();
            $t->date('closes_at')->nullable();
            $t->enum('status', ['open', 'closed'])->default('open');
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_talent_mobility_postings');
        Schema::dropIfExists('hrm_succession_candidates');
        Schema::dropIfExists('hrm_talent_pool_members');
        Schema::dropIfExists('hrm_career_path_employees');
        Schema::dropIfExists('hrm_career_milestones');
        Schema::dropIfExists('hrm_career_paths');
    }
};
