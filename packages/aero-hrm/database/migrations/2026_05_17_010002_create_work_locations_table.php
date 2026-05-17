<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('work_locations')) {
            Schema::create('work_locations', function (Blueprint $t) {
                $t->id();
                $t->string('name', 120);
                $t->string('type', 32)->default('office');
                $t->string('address')->nullable();
                $t->string('city', 80)->nullable();
                $t->string('country', 80)->nullable();
                $t->decimal('latitude', 10, 7)->nullable();
                $t->decimal('longitude', 10, 7)->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->softDeletes();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('work_locations'); }
};
