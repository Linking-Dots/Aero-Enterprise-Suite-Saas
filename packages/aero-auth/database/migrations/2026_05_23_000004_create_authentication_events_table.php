<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('authentication_events')) {
            return;
        }
        Schema::create('authentication_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);           // login|logout|mfa_challenge|password_reset|sso_callback|...
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status', 20);               // success|failed|blocked
            $table->string('risk_level', 20)->nullable(); // low|medium|high|critical
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index('event_type');
            $table->index('user_id');
            $table->index('status');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authentication_events');
    }
};
