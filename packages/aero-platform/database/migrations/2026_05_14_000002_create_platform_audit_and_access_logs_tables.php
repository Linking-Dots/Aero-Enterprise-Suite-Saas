<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Platform-level audit and access logs on the central database.
 * Records landlord admin actions, billing events, provisioning events.
 */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        Schema::connection('central')->create('platform_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_name')->nullable();
            $table->string('actor_ip', 45)->nullable();
            $table->text('actor_user_agent')->nullable();
            $table->string('event_type', 100)->index();
            $table->string('action', 100)->index();
            $table->text('description')->nullable();
            $table->string('subject_type')->nullable()->index();
            $table->string('subject_id', 36)->nullable()->index();
            $table->string('subject_label')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('changed_fields')->nullable();
            $table->string('session_id', 100)->nullable();
            $table->string('request_id', 100)->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('http_method', 10)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });

        // MySQL-only immutability triggers (SIGNAL not supported in SQLite/testing).
        if (DB::connection('central')->getDriverName() === 'mysql') {
            DB::connection('central')->unprepared('
                CREATE TRIGGER platform_audit_logs_prevent_update
                BEFORE UPDATE ON platform_audit_logs
                FOR EACH ROW
                SIGNAL SQLSTATE "45000"
                SET MESSAGE_TEXT = "platform_audit_logs records are immutable";
            ');

            DB::connection('central')->unprepared('
                CREATE TRIGGER platform_audit_logs_prevent_delete
                BEFORE DELETE ON platform_audit_logs
                FOR EACH ROW
                SIGNAL SQLSTATE "45000"
                SET MESSAGE_TEXT = "platform_audit_logs records are immutable";
            ');
        }

        Schema::connection('central')->create('platform_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accessor_id')->nullable()->index();
            $table->string('accessor_name')->nullable();
            $table->string('accessor_ip', 45)->nullable();
            $table->string('resource_type')->index();
            $table->string('resource_id', 36)->nullable();
            $table->string('subject_label')->nullable();
            $table->json('fields_accessed')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        DB::connection('central')->unprepared('DROP TRIGGER IF EXISTS platform_audit_logs_prevent_update');
        DB::connection('central')->unprepared('DROP TRIGGER IF EXISTS platform_audit_logs_prevent_delete');
        Schema::connection('central')->dropIfExists('platform_audit_logs');
        Schema::connection('central')->dropIfExists('platform_access_logs');
    }
};
