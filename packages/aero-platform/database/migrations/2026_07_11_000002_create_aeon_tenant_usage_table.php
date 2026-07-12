<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Central per-tenant AI usage summary, populated by the nightly aeon:rollup
     * job (which runs IN each tenant's context, so it can read the tenant-scoped
     * message counter + feedback that the platform admin can't read directly).
     * The fleet console reads this — cheap, correct, paginable — instead of
     * fanning out over every tenant per request.
     */
    public function up(): void
    {
        Schema::create('aeon_tenant_usage', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id');
            $t->string('period', 7);              // YYYY-MM (billing month)
            $t->boolean('enabled')->default(false); // AI in the tenant's plan?
            $t->integer('message_limit')->default(0); // -1 = unlimited
            $t->string('model', 20)->default('flash');
            $t->string('plan_name')->nullable(); // resolved once in the job (plan accessor is unsafe on central)
            $t->unsignedInteger('messages_used')->default(0);
            $t->unsignedInteger('feedback_up')->default(0);
            $t->unsignedInteger('feedback_down')->default(0);
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('synced_at')->nullable();
            $t->timestamps();

            $t->unique(['tenant_id', 'period']);
            $t->index('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aeon_tenant_usage');
    }
};
