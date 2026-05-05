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
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('schema'); // Form field definitions (type, label, validation, options, etc.)
            $table->json('conditional_logic')->nullable(); // Conditional rules for showing/hiding fields
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->boolean('allow_multiple_submissions')->default(true);
            $table->boolean('require_authentication')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->integer('max_submissions')->nullable();
            $table->string('success_message')->nullable();
            $table->string('redirect_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'is_published']);
            $table->index('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
