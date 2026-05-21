<?php

declare(strict_types=1);

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
        if (Schema::connection('central')->hasTable('invoice_settings')) {
            return;
        }

        Schema::connection('central')->create('invoice_settings', function (Blueprint $t) {
            $t->id();
            $t->string('prefix')->default('INV');
            $t->unsignedInteger('next_number')->default(1);
            $t->unsignedTinyInteger('digit_padding')->default(6);
            $t->foreignId('active_template_id')->nullable()->constrained('invoice_templates')->nullOnDelete();
            $t->string('logo_path')->nullable();
            $t->string('company_name')->nullable();
            $t->text('footer_text')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('invoice_settings');
    }
};
