<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->uuid('subscription_id')->nullable();
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();
            $table->string('event_type', 64); // plan_change, module_add, module_remove, cancel, resume, payment_method_update, admin_override
            $table->string('description', 512);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('performed_by_user_id')->nullable()->constrained('landlord_users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'event_type']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_audit_logs');
    }
};
