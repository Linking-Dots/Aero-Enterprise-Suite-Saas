<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->decimal('hours', 5, 2);
            $table->string('reason', 500);
            $table->string('status', 16)->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'status']);
            $table->index('work_date');
        });

        Schema::create('hrm_timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('week_start');
            $table->string('status', 16)->default('draft'); // draft, submitted, approved
            $table->decimal('total_hours', 6, 2)->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'week_start']);
        });

        Schema::create('hrm_timesheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timesheet_id')->constrained('hrm_timesheets')->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('project', 150)->nullable();
            $table->string('task', 250);
            $table->decimal('hours', 4, 2);
            $table->timestamps();
            $table->index(['timesheet_id', 'entry_date']);
        });

        Schema::create('hrm_shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('target_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('shift_date');
            $table->string('shift_label', 50);
            $table->string('status', 16)->default('open'); // open, matched, approved, rejected, cancelled
            $table->string('note', 500)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'shift_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_shift_swap_requests');
        Schema::dropIfExists('hrm_timesheet_entries');
        Schema::dropIfExists('hrm_timesheets');
        Schema::dropIfExists('hrm_overtime_requests');
    }
};
