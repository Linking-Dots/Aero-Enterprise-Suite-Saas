<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Flag demo tenants (democorp locally, demo.aeos365.com in prod) so the guided
 * live-demo experience (auto tour, exposed login creds, reset job, mail/SMS
 * guardrails) only activates for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'is_demo')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->boolean('is_demo')->default(false)->after('type');
            });
        }

        DB::table('tenants')
            ->whereIn('subdomain', ['demo', 'democorp'])
            ->update(['is_demo' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'is_demo')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('is_demo');
            });
        }
    }
};
