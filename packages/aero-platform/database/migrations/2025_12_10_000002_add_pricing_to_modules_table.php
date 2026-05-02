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
        Schema::table('modules', function (Blueprint $table) {
            $table->decimal('monthly_price', 10, 2)->default(0)->after('code');
            $table->decimal('yearly_price', 10, 2)->default(0)->after('monthly_price');
            $table->boolean('is_featured')->default(false)->after('yearly_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn(['monthly_price', 'yearly_price', 'is_featured']);
        });
    }
};
