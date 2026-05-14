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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('backup_id')->unique();
            $table->string('name');
            $table->string('type')->default('full');
            $table->bigInteger('size')->default(0);
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('storage_path');
            $table->string('storage_driver')->default('local');
            $table->boolean('encryption_status')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps(); // adds created_at + updated_at
            
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
