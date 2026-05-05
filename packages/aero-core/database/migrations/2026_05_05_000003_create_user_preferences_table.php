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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('preference_key', 100); // e.g., 'theme', 'locale', 'timezone', 'notifications.email_enabled'
            $table->text('preference_value')->nullable();
            $table->json('metadata')->nullable(); // Additional context or structured data
            $table->timestamps();
            
            $table->unique(['user_id', 'preference_key']);
            $table->index(['user_id', 'preference_key']);
            $table->index('preference_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
