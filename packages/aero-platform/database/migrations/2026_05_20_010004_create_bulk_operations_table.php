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
        if (Schema::connection('central')->hasTable('bulk_operations')) {
            return;
        }

        Schema::connection('central')->create('bulk_operations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32); // suspend|plan-change|email
            $table->json('payload');
            $table->string('status', 24)->default('queued'); // queued|running|completed|failed
            $table->unsignedBigInteger('created_by');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->timestamps();
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('bulk_operations');
    }
};
