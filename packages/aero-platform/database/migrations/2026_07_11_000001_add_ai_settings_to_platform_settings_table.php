<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add ai_settings JSON column to platform_settings.
     *
     * Central control-plane config for the tenant-facing Aeon assistant: which
     * provider/models to use, the (encrypted) API key, and the global token
     * "fuse" limits that sit under every plan's message quota. Editing this in
     * the platform admin updates every tenant instantly — no redeploy, no .env.
     *
     * Schema:
     * {
     *   "enabled":        bool,             master on/off for the fleet
     *   "provider":       "gemini"|"openai",
     *   "fast_model":     string,           model given to all AI-enabled tiers
     *   "premium_model":  string,           model unlocked on premium tiers
     *   "api_key":        string|null,      encrypted via Crypt::encryptString()
     *   "base_url":       string|null,      for openai-compatible endpoints
     *   "token_fuse_per_conversation": int, cost fuse (hidden from customers)
     *   "token_fuse_per_user_daily":   int,
     *   "max_tool_steps": int              agentic loop bound
     * }
     */
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->json('ai_settings')->nullable()->after('hosting_settings');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn('ai_settings');
        });
    }
};
