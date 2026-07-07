<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aeon_embeddings', function (Blueprint $t) {
            $t->id();
            $t->string('source_type')->index();   // module | doc
            $t->string('source_ref')->index();     // module code or file name
            $t->string('title')->nullable();
            $t->longText('chunk_text');
            $t->json('vector');
            $t->unsignedInteger('dims')->default(0);
            $t->string('checksum', 40)->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aeon_embeddings');
    }
};
