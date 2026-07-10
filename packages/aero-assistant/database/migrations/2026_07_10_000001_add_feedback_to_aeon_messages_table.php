<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aeon_messages', function (Blueprint $t) {
            // 1 = helpful, -1 = not helpful, null = no signal yet
            $t->tinyInteger('feedback')->nullable()->after('model');
        });
    }

    public function down(): void
    {
        Schema::table('aeon_messages', function (Blueprint $t) {
            $t->dropColumn('feedback');
        });
    }
};
