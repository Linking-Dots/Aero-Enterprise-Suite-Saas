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
        if (! Schema::connection('central')->hasTable('kb_articles')) {
            Schema::connection('central')->create('kb_articles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('body_md');
                $table->string('category')->nullable();
                $table->json('tags')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('view_count')->default(0);
                $table->uuid('author_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::connection('central')->hasTable('support_tickets')) {
            Schema::connection('central')->create('support_tickets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id')->index();
                $table->string('user_email');
                $table->string('subject');
                $table->string('status')->default('open');
                $table->string('priority')->default('normal');
                $table->uuid('assignee_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::connection('central')->hasTable('support_ticket_messages')) {
            Schema::connection('central')->create('support_ticket_messages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('ticket_id')->index();
                $table->string('author_type');
                $table->string('author_id');
                $table->text('body');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('support_ticket_messages');
        Schema::connection('central')->dropIfExists('support_tickets');
        Schema::connection('central')->dropIfExists('kb_articles');
    }
};
