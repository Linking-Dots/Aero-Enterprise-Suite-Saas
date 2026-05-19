<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hrm_expense_claim_receipts');
        Schema::dropIfExists('hrm_expense_claim_items');
        Schema::dropIfExists('hrm_expense_claims');
        Schema::dropIfExists('hrm_expense_categories');

        Schema::create('hrm_expense_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->text('description')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('hrm_expense_claims', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->unique();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->date('claim_date');
            $t->string('title');
            $t->text('description')->nullable();
            $t->decimal('total_amount', 12, 2)->default(0);
            $t->string('currency', 8)->default('USD');
            $t->string('status', 24)->default('draft');  // draft,submitted,approved,rejected,paid
            $t->foreignId('reviewed_by')->nullable()->constrained('users');
            $t->dateTime('reviewed_at')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['employee_id', 'status']);
        });

        Schema::create('hrm_expense_claim_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('claim_id')->constrained('hrm_expense_claims')->cascadeOnDelete();
            $t->foreignId('category_id')->constrained('hrm_expense_categories');
            $t->date('expense_date');
            $t->decimal('amount', 12, 2);
            $t->string('description')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_expense_claim_receipts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('claim_id')->constrained('hrm_expense_claims')->cascadeOnDelete();
            $t->string('path');
            $t->string('original_name');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_expense_claim_receipts');
        Schema::dropIfExists('hrm_expense_claim_items');
        Schema::dropIfExists('hrm_expense_claims');
        Schema::dropIfExists('hrm_expense_categories');
    }
};
