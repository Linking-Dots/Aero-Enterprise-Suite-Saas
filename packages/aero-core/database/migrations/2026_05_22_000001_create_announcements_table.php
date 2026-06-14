<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The canonical `announcements` table is created by aero-hrm
        // (2026_04_19_000001_create_employee_dashboard_tables) with a
        // published_at-based schema, and Aero\Core\Models\Announcement targets
        // that shape. This (divergent, status/softDeletes) definition would
        // otherwise win in tenant provisioning (core path runs before hrm) and
        // diverge from standalone. Defer to aero-hrm — HRM is always installed.
        if (Schema::hasTable('announcements')) {
            return;
        }

        // No-op create only if somehow announcements is needed before aero-hrm
        // runs; mirror the aero-hrm shape to stay consistent.
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('type')->default('general');
            $table->string('priority')->default('normal');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
