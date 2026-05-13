<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standalone_licenses', function (Blueprint $table) {
            $table->unique('external_order_id', 'standalone_licenses_external_order_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('standalone_licenses', function (Blueprint $table) {
            $table->dropUnique('standalone_licenses_external_order_id_unique');
        });
    }
};
