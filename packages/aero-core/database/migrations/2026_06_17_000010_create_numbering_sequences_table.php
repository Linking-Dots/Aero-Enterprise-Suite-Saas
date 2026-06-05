<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numbering sequences — per-entity auto-number counters (invoice, PO, etc.).
 * Tenant-scoped (lives in the tenant DB); backs NumberingController.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('numbering_sequences')) {
            return;
        }

        Schema::create('numbering_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 100)->unique();
            $table->string('prefix', 20)->nullable();
            $table->unsignedInteger('next_value')->default(1);
            $table->unsignedTinyInteger('padding')->default(4);
            $table->unsignedBigInteger('format_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_sequences');
    }
};
