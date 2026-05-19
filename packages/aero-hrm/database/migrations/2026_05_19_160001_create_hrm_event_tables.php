<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hrm_announcement_reads');
        Schema::dropIfExists('hrm_announcements');
        Schema::dropIfExists('hrm_event_registrations');
        Schema::dropIfExists('hrm_event_sessions');
        Schema::dropIfExists('hrm_events');

        Schema::create('hrm_events', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 120)->unique();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('location')->nullable();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->enum('status', ['draft', 'published', 'completed', 'cancelled'])->default('draft');
            $t->boolean('is_public')->default(false);
            $t->integer('capacity')->nullable();
            $t->foreignId('created_by')->constrained('users');
            $t->dateTime('published_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['status', 'starts_at']);
        });

        Schema::create('hrm_event_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained('hrm_events')->cascadeOnDelete();
            $t->string('title');
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->string('location')->nullable();
            $t->integer('capacity')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_event_registrations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained('hrm_events')->cascadeOnDelete();
            $t->foreignId('session_id')->nullable()->constrained('hrm_event_sessions')->nullOnDelete();
            $t->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->string('attendee_name');
            $t->string('attendee_email');
            $t->string('token', 64)->unique();
            $t->enum('status', ['registered', 'attended', 'cancelled'])->default('registered');
            $t->dateTime('registered_at');
            $t->dateTime('cancelled_at')->nullable();
            $t->timestamps();
            $t->index(['event_id', 'status']);
        });

        Schema::create('hrm_announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('body');
            $t->json('target_department_ids')->nullable();
            $t->json('target_role_ids')->nullable();
            $t->boolean('is_global')->default(false);
            $t->foreignId('created_by')->constrained('users');
            $t->dateTime('published_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('hrm_announcement_reads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('announcement_id')->constrained('hrm_announcements')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->timestamp('read_at');
            $t->unique(['announcement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_announcement_reads');
        Schema::dropIfExists('hrm_announcements');
        Schema::dropIfExists('hrm_event_registrations');
        Schema::dropIfExists('hrm_event_sessions');
        Schema::dropIfExists('hrm_events');
    }
};
