<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('email_suppression_list')) {
            return;
        }

        Schema::create('email_suppression_list', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique()->index();
            $table->string('reason')->default('manual'); // manual|bounce|complaint|unsubscribe
            $table->text('note')->nullable();
            // Plain column (no hard FK) — foundational shared package runs across central/
            // tenant/standalone whose user table differs (landlord_users vs users).
            $table->unsignedBigInteger('added_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppression_list');
    }
};
