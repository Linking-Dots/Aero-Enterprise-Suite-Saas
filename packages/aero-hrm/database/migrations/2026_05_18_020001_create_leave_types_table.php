<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80)->unique();
            $t->string('code', 16)->unique();
            $t->string('color', 16)->default('#3b82f6');
            $t->decimal('days_per_year', 5, 2)->default(0);
            $t->boolean('is_paid')->default(true);
            $t->boolean('requires_approval')->default(true);
            $t->boolean('carry_forward')->default(false);
            $t->boolean('encashable')->default(false);
            $t->decimal('max_carry_forward', 5, 2)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
