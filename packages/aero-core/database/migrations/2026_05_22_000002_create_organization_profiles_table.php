<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('organization_profiles')) {
            return;
        }

        Schema::create('organization_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->text('tax_id')->nullable(); // EncryptedField
            $table->string('vat_number')->nullable();
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('fiscal_year_start')->nullable(); // e.g. "01-01"
            $table->string('fiscal_year_end')->nullable();   // e.g. "12-31"
            $table->string('timezone')->nullable();
            $table->string('date_format')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('addresses')->nullable();  // JSON array of address objects
            $table->json('contacts')->nullable();   // JSON array of contact objects
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_profiles');
    }
};
