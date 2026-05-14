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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('commentable'); // Allows comments on any model (polymorphic)
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade'); // For nested/reply comments
            $table->text('content');
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->json('metadata')->nullable(); // For storing reactions, mentions, etc.
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['commentable_id', 'commentable_type']);
            $table->index('user_id');
            $table->index('parent_id');
            $table->index('created_at');
        });

        Schema::create('comment_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->onDelete('cascade');
            $table->foreignId('mentioned_user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->unique(['comment_id', 'mentioned_user_id']);
            $table->index('mentioned_user_id');
            $table->index('is_read');
        });

        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reaction_type'); // e.g., 'like', 'love', 'thumbs_up', etc.
            $table->timestamps();
            
            $table->unique(['comment_id', 'user_id', 'reaction_type']);
            $table->index('comment_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
        Schema::dropIfExists('comment_mentions');
        Schema::dropIfExists('comments');
    }
};
