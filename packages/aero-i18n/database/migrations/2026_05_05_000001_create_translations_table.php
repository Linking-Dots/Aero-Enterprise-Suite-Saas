<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('language_code', 10); // e.g., 'en', 'es', 'fr'
            $table->string('key', 255); // translation key
            $table->text('value')->nullable(); // translated value
            $table->string('namespace', 100)->nullable(); // e.g., 'core', 'hrm', 'finance'
            $table->string('group', 100)->nullable(); // e.g., 'users', 'employees'
            $table->boolean('is_custom')->default(false); // custom override vs system default
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['language_code', 'key', 'namespace', 'group'], 'translations_unique');
            $table->index('language_code');
            $table->index('key');
            $table->index('namespace');
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
