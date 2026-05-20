<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection('central')->hasTable('quota_enforcement_settings')) {
            return;
        }

        Schema::create('quota_enforcement_settings', function (Blueprint $table) {
            $table->id();
            $table->string('quota_type', 50)->unique(); // 'users', 'storage_gb', 'api_calls_monthly', etc.
            $table->unsignedTinyInteger('warning_threshold_percentage')->default(80); // Warn at 80%
            $table->unsignedTinyInteger('critical_threshold_percentage')->default(90); // Critical at 90%
            $table->unsignedTinyInteger('block_threshold_percentage')->default(100); // Block at 100%
            $table->unsignedTinyInteger('warning_period_days')->default(10); // Grace period in days
            $table->boolean('send_email')->default(true);
            $table->boolean('send_sms')->default(false);
            $table->boolean('block_on_exceed')->default(true);
            $table->string('escalation_frequency')->default('daily'); // 'daily', 'hourly', 'realtime'
            $table->json('notification_preferences')->nullable(); // Additional notification config
            $table->timestamps();

            // Index for quick lookups
            $table->index('quota_type');
        });

        // Insert default settings for common quota types
        DB::table('quota_enforcement_settings')->insert([
            [
                'quota_type' => 'users',
                'warning_threshold_percentage' => 80,
                'critical_threshold_percentage' => 90,
                'block_threshold_percentage' => 100,
                'warning_period_days' => 10,
                'send_email' => true,
                'send_sms' => false,
                'block_on_exceed' => true,
                'escalation_frequency' => 'daily',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'quota_type' => 'storage_gb',
                'warning_threshold_percentage' => 80,
                'critical_threshold_percentage' => 90,
                'block_threshold_percentage' => 100,
                'warning_period_days' => 10,
                'send_email' => true,
                'send_sms' => false,
                'block_on_exceed' => true,
                'escalation_frequency' => 'daily',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'quota_type' => 'api_calls_monthly',
                'warning_threshold_percentage' => 90,
                'critical_threshold_percentage' => 95,
                'block_threshold_percentage' => 100,
                'warning_period_days' => 10,
                'send_email' => true,
                'send_sms' => true,
                'block_on_exceed' => true,
                'escalation_frequency' => 'hourly',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'quota_type' => 'employees',
                'warning_threshold_percentage' => 80,
                'critical_threshold_percentage' => 90,
                'block_threshold_percentage' => 100,
                'warning_period_days' => 10,
                'send_email' => true,
                'send_sms' => false,
                'block_on_exceed' => true,
                'escalation_frequency' => 'daily',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'quota_type' => 'projects',
                'warning_threshold_percentage' => 80,
                'critical_threshold_percentage' => 90,
                'block_threshold_percentage' => 100,
                'warning_period_days' => 10,
                'send_email' => true,
                'send_sms' => false,
                'block_on_exceed' => true,
                'escalation_frequency' => 'daily',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quota_enforcement_settings');
    }
};
