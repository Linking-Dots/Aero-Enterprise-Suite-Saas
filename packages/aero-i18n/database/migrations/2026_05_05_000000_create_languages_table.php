<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // e.g., 'en', 'es', 'fr'
            $table->string('name', 100); // e.g., 'English', 'Spanish', 'French'
            $table->string('native_name', 100); // e.g., 'English', 'Español', 'Français'
            $table->string('flag', 10)->nullable(); // emoji flag: 🇺🇸, 🇪🇸, 🇫🇷
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_rtl')->default(false); // right-to-left support
            $table->string('direction', 3)->default('ltr'); // ltr or rtl
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
