<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Alter job_applications — add rejection columns
        Schema::table('job_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('job_applications', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('job_applications', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            }
            if (! Schema::hasColumn('job_applications', 'rejected_by')) {
                $table->foreignId('rejected_by')
                    ->nullable()
                    ->after('rejected_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        // 2. Alter job_offers — add acceptance / decline columns
        Schema::table('job_offers', function (Blueprint $table) {
            if (! Schema::hasColumn('job_offers', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('responded_at');
            }
            if (! Schema::hasColumn('job_offers', 'declined_at')) {
                $table->timestamp('declined_at')->nullable()->after('accepted_at');
            }
            if (! Schema::hasColumn('job_offers', 'signed_document_path')) {
                $table->string('signed_document_path')->nullable()->after('declined_at');
            }
        });

        // 3. Create onboarding_runs table
        if (! Schema::hasTable('onboarding_runs')) {
            Schema::create('onboarding_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')
                    ->constrained('job_applications')
                    ->cascadeOnDelete();
                $table->foreignId('employee_id')
                    ->nullable()
                    ->constrained('employees')
                    ->nullOnDelete();
                $table->string('status', 16)->default('pending');
                $table->json('checklist')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            if (Schema::hasColumn('job_applications', 'rejected_by')) {
                $table->dropForeign(['rejected_by']);
                $table->dropColumn('rejected_by');
            }
            if (Schema::hasColumn('job_applications', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            if (Schema::hasColumn('job_applications', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });

        Schema::table('job_offers', function (Blueprint $table) {
            if (Schema::hasColumn('job_offers', 'signed_document_path')) {
                $table->dropColumn('signed_document_path');
            }
            if (Schema::hasColumn('job_offers', 'declined_at')) {
                $table->dropColumn('declined_at');
            }
            if (Schema::hasColumn('job_offers', 'accepted_at')) {
                $table->dropColumn('accepted_at');
            }
        });

        Schema::dropIfExists('onboarding_runs');
    }
};
