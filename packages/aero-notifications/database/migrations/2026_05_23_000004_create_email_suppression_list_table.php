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
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppression_list');
    }
};
