<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Print templates — per-entity printable layouts (paper size, margins, HTML).
 * Tenant-scoped; backs PrintTemplateController.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('print_templates')) {
            return;
        }

        Schema::create('print_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('entity_type')->index();
            $table->longText('template');
            $table->string('paper_size', 20)->default('A4');
            $table->string('orientation', 20)->default('portrait');
            $table->decimal('margin_top', 6, 2)->default(0);
            $table->decimal('margin_right', 6, 2)->default(0);
            $table->decimal('margin_bottom', 6, 2)->default(0);
            $table->decimal('margin_left', 6, 2)->default(0);
            $table->text('header_html')->nullable();
            $table->text('footer_html')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['entity_type', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_templates');
    }
};
