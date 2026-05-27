<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('session_policies')) {
            return;
        }
        Schema::create('session_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('session_lifetime_minutes')->default(120);
            $table->boolean('single_session_per_user')->default(false);
            $table->unsignedInteger('max_concurrent_sessions')->nullable();
            $table->boolean('force_logout_on_password_change')->default(true);
            $table->boolean('require_fresh_auth_for_sensitive')->default(false);
            $table->unsignedInteger('idle_timeout_minutes')->nullable();
            $table->timestamps();
        });

        DB::table('session_policies')->insert([
            'session_lifetime_minutes'        => 120,
            'single_session_per_user'         => false,
            'force_logout_on_password_change' => true,
            'created_at'                      => now(),
            'updated_at'                      => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('session_policies');
    }
};
