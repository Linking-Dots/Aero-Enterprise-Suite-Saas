<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hrm_asset_allocations');
        Schema::dropIfExists('hrm_assets');
        Schema::dropIfExists('hrm_asset_categories');

        Schema::create('hrm_asset_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->text('description')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('hrm_assets', function (Blueprint $t) {
            $t->id();
            $t->string('tag', 64)->unique();
            $t->string('name');
            $t->foreignId('category_id')->constrained('hrm_asset_categories')->cascadeOnDelete();
            $t->string('serial_number')->nullable();
            $t->string('vendor')->nullable();
            $t->date('purchased_on')->nullable();
            $t->decimal('purchase_cost', 12, 2)->default(0);
            $t->enum('status', ['available', 'allocated', 'maintenance', 'retired'])->default('available');
            $t->string('photo_path')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['status', 'category_id']);
        });

        Schema::create('hrm_asset_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('asset_id')->constrained('hrm_assets')->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->dateTime('allocated_at');
            $t->dateTime('returned_at')->nullable();
            $t->string('condition_on_allocation', 32)->nullable();
            $t->string('condition_on_return', 32)->nullable();
            $t->text('allocation_notes')->nullable();
            $t->text('return_notes')->nullable();
            $t->foreignId('allocated_by')->constrained('users');
            $t->foreignId('returned_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->index(['asset_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_asset_allocations');
        Schema::dropIfExists('hrm_assets');
        Schema::dropIfExists('hrm_asset_categories');
    }
};
