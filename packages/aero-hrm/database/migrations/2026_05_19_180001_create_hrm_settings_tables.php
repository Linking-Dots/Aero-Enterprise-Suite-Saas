<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hrm_task_templates');
        Schema::dropIfExists('hrm_public_holidays');

        Schema::create('hrm_public_holidays', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->date('date');
            $t->boolean('is_optional')->default(false);
            $t->timestamps();
            $t->index('date');
        });

        Schema::create('hrm_task_templates', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->enum('type', ['onboarding', 'offboarding']);
            $t->text('description')->nullable();
            $t->json('tasks');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_task_templates');
        Schema::dropIfExists('hrm_public_holidays');
    }
};
