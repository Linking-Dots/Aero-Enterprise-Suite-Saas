<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PartnerService has queried tenants.reseller_partner_id since P-9, but the
 * column was never created (and stancl's VirtualColumn can't satisfy a SQL
 * WHERE), so partner-tenant listing and reassignment 500ed. Real column +
 * index; nullable because most tenants are direct, not partner-managed.
 */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        if (Schema::connection('central')->hasColumn('tenants', 'reseller_partner_id')) {
            return;
        }

        Schema::connection('central')->table('tenants', function (Blueprint $t) {
            $t->unsignedBigInteger('reseller_partner_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasColumn('tenants', 'reseller_partner_id')) {
            return;
        }

        Schema::connection('central')->table('tenants', function (Blueprint $t) {
            $t->dropIndex(['reseller_partner_id']);
            $t->dropColumn('reseller_partner_id');
        });
    }
};
