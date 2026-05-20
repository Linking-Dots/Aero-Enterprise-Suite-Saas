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

    public function up(): void
    {
        if (Schema::connection('central')->hasTable('platform_metrics_daily')) {
            return;
        }

        Schema::connection('central')->create('platform_metrics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            // ARCH NOTE: mrr/arr are TOTALS = plan_mrr + product_mrr. Per locked architecture,
            // MRR/ARR = sum(subscriptions revenue) + sum(product_subscriptions revenue).
            $table->decimal('mrr', 14, 2)->default(0);
            $table->decimal('arr', 14, 2)->default(0);
            $table->decimal('plan_mrr', 14, 2)->default(0);
            $table->decimal('product_mrr', 14, 2)->default(0);
            $table->decimal('plan_arr', 14, 2)->default(0);
            $table->decimal('product_arr', 14, 2)->default(0);
            $table->unsignedInteger('new_tenants')->default(0);
            $table->unsignedInteger('churned_tenants')->default(0);
            $table->unsignedInteger('active_tenants')->default(0);
            $table->unsignedInteger('trial_tenants')->default(0);
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->timestamps();
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_metrics_daily');
    }
};
