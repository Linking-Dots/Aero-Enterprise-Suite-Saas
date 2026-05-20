<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        if (Schema::connection('central')->hasTable('tenant_provisioning_logs')) {
            return;
        }

        Schema::connection('central')->create('tenant_provisioning_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('status', 24); // pending|running|completed|failed
            $table->string('step')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_provisioning_logs');
    }
};
