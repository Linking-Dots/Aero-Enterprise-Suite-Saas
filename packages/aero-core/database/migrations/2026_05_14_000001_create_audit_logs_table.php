<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tenant audit log (runs inside tenant DB via stancl/tenancy)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Actor
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_name')->nullable();
            $table->string('actor_ip', 45)->nullable();
            $table->text('actor_user_agent')->nullable();

            // Event
            $table->string('event_type', 100)->index();
            $table->string('action', 100)->index();
            $table->text('description')->nullable();

            // Subject
            $table->string('subject_type')->nullable()->index();
            $table->string('subject_id', 36)->nullable()->index();
            $table->string('subject_label')->nullable();

            // Changes
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('changed_fields')->nullable();

            // Request context
            $table->string('session_id', 100)->nullable();
            $table->string('request_id', 100)->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('http_method', 10)->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamp('anonymized_at')->nullable();

            // NO updated_at — immutable by design
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });

        // DB-level protection: prevent UPDATE and DELETE on audit_logs
        // Uses a trigger to enforce immutability at the database layer
        DB::unprepared('
            CREATE TRIGGER audit_logs_prevent_update
            BEFORE UPDATE ON audit_logs
            FOR EACH ROW
            SIGNAL SQLSTATE "45000"
            SET MESSAGE_TEXT = "audit_logs records are immutable and cannot be updated";
        ');

        DB::unprepared('
            CREATE TRIGGER audit_logs_prevent_delete
            BEFORE DELETE ON audit_logs
            FOR EACH ROW
            SIGNAL SQLSTATE "45000"
            SET MESSAGE_TEXT = "audit_logs records are immutable and cannot be deleted";
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_prevent_update');
        DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_prevent_delete');
        Schema::dropIfExists('audit_logs');
    }
};
