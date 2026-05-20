<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        if (Schema::connection('central')->hasTable('funnel_definitions')) {
            return;
        }

        Schema::connection('central')->create('funnel_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->json('steps'); // [{event, label}]
            $table->foreignId('created_by')->constrained('landlord_users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('funnel_definitions');
    }
};
