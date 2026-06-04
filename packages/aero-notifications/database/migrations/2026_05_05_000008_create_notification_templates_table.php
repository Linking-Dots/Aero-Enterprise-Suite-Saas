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
        if (Schema::hasTable('notification_templates')) {
            return;
        }

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            // tenant_id is a plain column here. A FK to `tenants` is invalid in
            // this tenancy model: in standalone there is no `tenants` table, and
            // in SaaS `tenants` lives in the central DB (cross-DB FK). The FK is
            // added below only when a `tenants` table exists on this connection.
            $table->foreignId('tenant_id')->nullable();
            $table->string('name');
            $table->string('subject');
            $table->text('html_content');
            $table->text('plain_content')->nullable();
            $table->json('variables')->nullable();
            $table->string('category')->default('system');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['tenant_id', 'category']);
            $table->index('category');
            $table->index('is_active');
            $table->index('is_system');
        });

        // SaaS single-DB / central deployments that carry a `tenants` table get
        // the referential constraint; standalone and per-tenant DBs skip it.
        if (Schema::hasTable('tenants')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
