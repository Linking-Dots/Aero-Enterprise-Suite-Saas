<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('webhooks')) {
            Schema::create('webhooks', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('url');
                $table->json('events'); // array of event types to subscribe to
                $table->text('secret_hash'); // hashed HMAC secret
                $table->string('secret_prefix', 8); // for display
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_triggered_at')->nullable();
                $table->unsignedInteger('failure_count')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('is_active');
            });
        }

        if (! Schema::hasTable('webhook_deliveries')) {
            Schema::create('webhook_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('webhook_id')->constrained('webhooks')->cascadeOnDelete();
                $table->string('event_type');
                $table->json('payload');
                $table->integer('http_status')->nullable();
                $table->text('response_body')->nullable();
                $table->text('error_message')->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->string('status')->default('pending'); // pending|success|failed
                $table->timestamp('delivered_at')->nullable();
                $table->timestamps();

                $table->index(['webhook_id', 'status']);
                $table->index('event_type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
    }
};
