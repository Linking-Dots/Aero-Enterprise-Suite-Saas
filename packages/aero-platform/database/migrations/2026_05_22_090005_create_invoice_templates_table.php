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
        if (Schema::connection('central')->hasTable('invoice_templates')) {
            return;
        }

        Schema::connection('central')->create('invoice_templates', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->longText('html_body');
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('invoice_templates');
    }
};
