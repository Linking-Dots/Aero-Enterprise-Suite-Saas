<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standalone_licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->onDelete('restrict');
            $table->string('license_key', 64)->unique();
            $table->string('customer_email');
            $table->string('customer_name')->nullable();
            $table->string('status')->default('active');
            $table->string('bound_domain_hash', 64)->nullable();
            $table->integer('activation_count')->default(0);
            $table->integer('max_activations')->default(1);
            $table->string('purchase_source')->nullable();
            $table->string('external_order_id')->nullable();
            $table->string('billing_type')->default('one_time');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_validated_at')->nullable();
            $table->string('current_version')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_email');
            $table->index('status');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standalone_licenses');
    }
};
