<?php

declare(strict_types=1);

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
        Schema::connection('central')->table('invoices', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('invoices', 'reference')) {
                $table->string('reference', 32)->unique()->nullable()->after('id');
            }
            if (! Schema::connection('central')->hasColumn('invoices', 'amount')) {
                $table->decimal('amount', 12, 2)->default(0)->after('reference');
            }
            if (! Schema::connection('central')->hasColumn('invoices', 'stripe_invoice_id')) {
                $table->string('stripe_invoice_id')->nullable()->after('external_invoice_id');
            }
            if (! Schema::connection('central')->hasColumn('invoices', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('stripe_invoice_id');
            }
        });
    }

    public function down(): void
    {
        // Drop indexes before dropping columns — required for SQLite compatibility.
        Schema::connection('central')->table('invoices', function (Blueprint $table) {
            if (Schema::connection('central')->hasColumn('invoices', 'reference')) {
                // Drop the unique index before dropping the column (SQLite requirement).
                try {
                    $table->dropUnique(['reference']);
                } catch (\Throwable) {
                    // Index may not exist in all environments.
                }
            }
        });

        Schema::connection('central')->table('invoices', function (Blueprint $table) {
            $cols = array_filter([
                Schema::connection('central')->hasColumn('invoices', 'reference') ? 'reference' : null,
                Schema::connection('central')->hasColumn('invoices', 'amount') ? 'amount' : null,
                Schema::connection('central')->hasColumn('invoices', 'stripe_invoice_id') ? 'stripe_invoice_id' : null,
                Schema::connection('central')->hasColumn('invoices', 'pdf_path') ? 'pdf_path' : null,
            ]);
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
