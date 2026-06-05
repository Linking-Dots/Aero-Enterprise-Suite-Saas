<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numbering formats — reusable pattern definitions (e.g. "INV-{YYYY}-{SEQ}").
 * Tenant-scoped; backs NumberingController format management.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('numbering_formats')) {
            return;
        }

        Schema::create('numbering_formats', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('pattern', 100);
            $table->string('example', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_formats');
    }
};
