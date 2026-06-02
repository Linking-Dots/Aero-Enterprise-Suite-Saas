<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy training tables that were created by the 2025-12-02 migration
        // with an incompatible schema. H8 supersedes them with the correct structure.
        // The legacy `training_feedback` table FK-references training_enrollments,
        // so drop it first and disable FK checks to make the drops order-independent.
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('training_feedback');
        Schema::dropIfExists('training_enrollments');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('training_categories');
        Schema::enableForeignKeyConstraints();

        // 1. training_categories
        if (! Schema::hasTable('training_categories')) {
            Schema::create('training_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('color', 16)->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. training_courses
        if (! Schema::hasTable('training_courses')) {
            Schema::create('training_courses', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('category_id')->constrained('training_categories')->restrictOnDelete();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('summary')->nullable();
                $table->longText('description')->nullable();
                $table->enum('delivery_mode', ['in_person', 'virtual', 'self_paced'])->default('in_person');
                $table->unsignedInteger('duration_minutes')->default(60);
                $table->json('learning_objectives')->nullable();
                $table->json('prerequisites')->nullable();
                $table->json('tags')->nullable();
                $table->boolean('is_mandatory')->default(false);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. training_course_materials
        if (! Schema::hasTable('training_course_materials')) {
            Schema::create('training_course_materials', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('course_id')->constrained('training_courses')->cascadeOnDelete();
                $table->enum('kind', ['link', 'file'])->default('link');
                $table->string('label');
                $table->string('url')->nullable();
                $table->string('file_path')->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();
            });
        }

        // 4. training_sessions
        if (! Schema::hasTable('training_sessions')) {
            Schema::create('training_sessions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('course_id')->constrained('training_courses')->cascadeOnDelete();
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->string('location')->nullable();
                $table->string('meeting_link')->nullable();
                $table->unsignedInteger('capacity')->default(20);
                $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 16)->default('scheduled');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
            });
        }

        // 5. training_enrollments
        if (! Schema::hasTable('training_enrollments')) {
            Schema::create('training_enrollments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('session_id')->constrained('training_sessions')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('status', 16)->default('enrolled');
                $table->string('source', 8)->default('admin');
                $table->timestamp('attended_at')->nullable();
                $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('enrolled_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->unique(['session_id', 'employee_id']);
            });
        }

        // 6. training_feedbacks
        if (! Schema::hasTable('training_feedbacks')) {
            Schema::create('training_feedbacks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('enrollment_id')->constrained('training_enrollments')->cascadeOnDelete();
                $table->tinyInteger('content_rating')->unsigned();
                $table->tinyInteger('instructor_rating')->unsigned();
                $table->tinyInteger('overall_rating')->unsigned();
                $table->boolean('would_recommend')->default(false);
                $table->text('what_worked')->nullable();
                $table->text('what_didnt_work')->nullable();
                $table->text('suggestions')->nullable();
                $table->timestamp('submitted_at');
                $table->timestamps();
                $table->unique('enrollment_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('training_feedbacks');
        Schema::dropIfExists('training_enrollments');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('training_course_materials');
        Schema::dropIfExists('training_courses');
        Schema::dropIfExists('training_categories');
    }
};
