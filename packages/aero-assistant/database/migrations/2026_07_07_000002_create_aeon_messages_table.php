<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aeon_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained('aeon_conversations')->cascadeOnDelete();
            $t->string('role'); // user | assistant | tool
            $t->longText('content')->nullable();
            $t->json('blocks')->nullable();
            $t->json('tool_calls')->nullable();
            $t->unsignedInteger('tokens')->default(0);
            $t->string('provider')->nullable();
            $t->string('model')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aeon_messages');
    }
};
