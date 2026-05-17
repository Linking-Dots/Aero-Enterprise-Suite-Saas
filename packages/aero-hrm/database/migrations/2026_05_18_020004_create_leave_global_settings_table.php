<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_global_settings', function (Blueprint $t) {
            $t->id();
            $t->string('accrual_cycle', 16)->default('yearly');
            $t->boolean('allow_negative_balance')->default(false);
            $t->boolean('auto_approve_under_days')->default(false);
            $t->decimal('auto_approve_threshold', 4, 2)->default(0);
            $t->boolean('encashment_enabled')->default(false);
            $t->json('extra')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_global_settings');
    }
};
