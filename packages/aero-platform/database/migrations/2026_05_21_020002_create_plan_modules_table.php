<?php

declare(strict_types=1);

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
        if (Schema::connection('central')->hasTable('plan_modules')) {
            return;
        }

        Schema::connection('central')->create('plan_modules', function (Blueprint $table) {
            $table->id();
            $table->char('plan_id', 36);
            $table->string('module_code', 64);
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
            $table->unique(['plan_id', 'module_code']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('plan_modules');
    }
};
