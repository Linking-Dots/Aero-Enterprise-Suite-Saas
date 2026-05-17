<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $t->date('start_date');
            $t->date('end_date');
            $t->decimal('total_days', 5, 2);
            $t->string('status', 16)->default('pending');
            $t->text('reason')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('rejected_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['employee_id', 'start_date']);
            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
