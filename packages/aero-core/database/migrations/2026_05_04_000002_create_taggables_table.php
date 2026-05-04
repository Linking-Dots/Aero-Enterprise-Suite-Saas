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
        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tag_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('taggable_type');
            $table->unsignedBigInteger('taggable_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at');

            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['tag_id', 'taggable_type', 'taggable_id', 'tenant_id'], 'taggables_unique');
            $table->index(['taggable_type', 'taggable_id', 'tenant_id'], 'taggables_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
