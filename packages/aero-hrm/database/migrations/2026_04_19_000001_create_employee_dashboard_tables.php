<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Canonical announcements table. aero-core's create_announcements_table
        // defers to this one, but guard anyway so migration order can't collide.
        if (! Schema::hasTable('announcements')) {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('type')->default('general');
            $table->string('priority')->default('normal');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['published_at', 'expires_at']);
        });
        }

        Schema::create('recognitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('employees')->cascadeOnDelete();
            $table->text('message');
            $table->string('badge_type')->default('kudos');
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index('recipient_id');
        });

        Schema::create('daily_check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->enum('mood', ['great', 'good', 'okay', 'bad', 'terrible']);
            $table->text('note')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
        });

        Schema::create('policy_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('hr_documents')->cascadeOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('version')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'document_id', 'version']);
        });

        Schema::create('employee_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('label');
            $table->string('route');
            $table->string('icon')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('employee_id');
        });

        Schema::create('work_location_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->enum('location_type', ['office', 'home', 'hybrid', 'field'])->default('office');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_location_logs');
        Schema::dropIfExists('employee_bookmarks');
        Schema::dropIfExists('policy_acknowledgements');
        Schema::dropIfExists('daily_check_ins');
        Schema::dropIfExists('recognitions');
        Schema::dropIfExists('announcements');
    }
};
