<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('slug');
            $table->string('color', 7)->default('#0ea5e9'); // hex color
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
