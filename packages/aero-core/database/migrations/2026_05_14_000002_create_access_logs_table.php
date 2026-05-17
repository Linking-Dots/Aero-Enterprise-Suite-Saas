<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accessor_id')->nullable()->index();
            $table->string('accessor_name')->nullable();
            $table->string('accessor_ip', 45)->nullable();
            $table->string('resource_type')->index();
            $table->string('resource_id', 36)->nullable()->index();
            $table->string('subject_label')->nullable();
            $table->json('fields_accessed')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['resource_type', 'resource_id']);
        });

        // MySQL-only immutability triggers — skipped on SQLite/testing
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared('
                CREATE TRIGGER access_logs_prevent_update
                BEFORE UPDATE ON access_logs
                FOR EACH ROW
                SIGNAL SQLSTATE "45000"
                SET MESSAGE_TEXT = "access_logs records are immutable";
            ');

            DB::unprepared('
                CREATE TRIGGER access_logs_prevent_delete
                BEFORE DELETE ON access_logs
                FOR EACH ROW
                SIGNAL SQLSTATE "45000"
                SET MESSAGE_TEXT = "access_logs records are immutable";
            ');
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS access_logs_prevent_update');
        DB::unprepared('DROP TRIGGER IF EXISTS access_logs_prevent_delete');
        Schema::dropIfExists('access_logs');
    }
};
