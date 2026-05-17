<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('grades')) {
            Schema::create('grades', function (Blueprint $t) {
                $t->id();
                $t->string('name', 64);
                $t->string('code', 16)->nullable();
                $t->decimal('min_salary', 12, 2)->nullable();
                $t->decimal('max_salary', 12, 2)->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->softDeletes();
                $t->unique(['name']);
            });
        }
    }
    public function down(): void { Schema::dropIfExists('grades'); }
};
