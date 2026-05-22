<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('tenant_health_scores')) {
            Schema::connection('central')->create('tenant_health_scores', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->index();
                $table->unsignedTinyInteger('score')->default(0);
                $table->json('signals')->nullable();
                $table->string('trend')->default('stable');
                $table->timestamp('computed_at')->nullable();
                $table->timestamps();
                $table->unique('tenant_id');
            });
        }

        if (! Schema::connection('central')->hasTable('nps_survey_responses')) {
            Schema::connection('central')->create('nps_survey_responses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->index();
                $table->string('user_email');
                $table->unsignedTinyInteger('score');
                $table->text('comment')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('csm_assignments')) {
            Schema::connection('central')->create('csm_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->unique()->index();
                $table->uuid('csm_user_id')->nullable()->index();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('success_playbooks')) {
            Schema::connection('central')->create('success_playbooks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('steps')->nullable();
                $table->string('trigger')->default('manual');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::connection('central')->hasTable('playbook_runs')) {
            Schema::connection('central')->create('playbook_runs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('playbook_id')->index();
                $table->string('tenant_id')->index();
                $table->string('status')->default('pending');
                $table->json('step_results')->nullable();
                $table->uuid('executed_by')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('playbook_runs');
        Schema::connection('central')->dropIfExists('success_playbooks');
        Schema::connection('central')->dropIfExists('csm_assignments');
        Schema::connection('central')->dropIfExists('nps_survey_responses');
        Schema::connection('central')->dropIfExists('tenant_health_scores');
    }
};
