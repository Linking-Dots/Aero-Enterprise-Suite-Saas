<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * White-Label P3 — bring central per-tenant branding to full BrandStudio
 * parity (name + the complete 5-asset taxonomy), plus honest operational
 * state for the two console tabs: a custom-CSS kill switch and a DKIM
 * verification timestamp.
 */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        Schema::connection('central')->table('tenant_brandings', function (Blueprint $table) {
            $table->string('name')->nullable()->after('tenant_id');
            $table->string('logo_dark_path')->nullable()->after('logo_path');
            $table->string('logo_icon_path')->nullable()->after('logo_dark_path');
            $table->string('login_background_path')->nullable()->after('favicon_path');
            $table->boolean('css_disabled')->default(false)->after('custom_css_path');
            $table->timestamp('dkim_verified_at')->nullable()->after('dkim_private_key');

            // Colors are chain overrides now — null means "inherit platform brand",
            // so the old hard defaults become nullable with no default.
            $table->string('primary_color', 7)->nullable()->default(null)->change();
            $table->string('secondary_color', 7)->nullable()->default(null)->change();
        });

        // Rows created under the old defaults carry stock colors that were never
        // chosen — normalize them to "inherit".
        DB::connection('central')->table('tenant_brandings')
            ->where('primary_color', '#3B82F6')->update(['primary_color' => null]);
        DB::connection('central')->table('tenant_brandings')
            ->where('secondary_color', '#1E40AF')->update(['secondary_color' => null]);
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenant_brandings', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'logo_dark_path', 'logo_icon_path',
                'login_background_path', 'css_disabled', 'dkim_verified_at',
            ]);
        });
    }
};
