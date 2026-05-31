<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('mfa_policies')) {
            return;
        }
        Schema::create('mfa_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('applies_to_roles');
            $table->string('required_method')->default('any'); // any|totp|sms|email
            $table->boolean('allow_remember_device')->default(true);
            $table->unsignedInteger('remember_device_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_policies');
    }
};
