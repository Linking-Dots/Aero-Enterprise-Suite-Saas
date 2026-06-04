<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retire the legacy landlord role tables.
 *
 * Platform/landlord roles now use the shared HRMAC engine (central `roles` +
 * `role_module_access` + `model_has_roles`), so `landlord_roles` and the
 * `landlord_user_role` pivot are unused. They were always empty in practice.
 * down() recreates them (matching the original schema) for reversibility.
 */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        Schema::connection('central')->dropIfExists('landlord_user_role');
        Schema::connection('central')->dropIfExists('landlord_roles');
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('landlord_roles')) {
            Schema::connection('central')->create('landlord_roles', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120)->unique();
                $table->string('description', 255)->nullable();
                $table->json('permissions')->nullable();
                $table->boolean('is_system')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('landlord_user_role')) {
            Schema::connection('central')->create('landlord_user_role', function (Blueprint $table) {
                $table->id();
                $table->foreignId('landlord_user_id')->constrained('landlord_users')->cascadeOnDelete();
                $table->foreignId('landlord_role_id')->constrained('landlord_roles')->cascadeOnDelete();
                $table->unique(['landlord_user_id', 'landlord_role_id']);
                $table->timestamps();
            });
        }
    }
};
