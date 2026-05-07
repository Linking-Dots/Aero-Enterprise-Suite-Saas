<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installed_addons', function (Blueprint $table) {
            $table->id();
            $table->string('module_code')->unique();
            $table->string('product_code')->unique();
            $table->string('name');
            $table->string('version');
            $table->string('license_key');
            $table->string('install_path');
            $table->string('status')->default('active');
            $table->timestamp('installed_at');
            $table->timestamp('last_checked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installed_addons');
    }
};
