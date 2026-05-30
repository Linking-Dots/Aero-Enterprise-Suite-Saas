<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the finance_journal_entries table for double-entry bookkeeping.
     * Each entry must have balanced debits and credits.
     */
    public function up(): void
    {
        Schema::create('finance_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('Transaction date');
            $table->string('reference', 100)->comment('Reference number or invoice number');
            $table->text('description')->nullable();
            $table->enum('type', ['standard', 'adjusting', 'closing'])->default('standard');
            $table->enum('status', ['draft', 'posted', 'voided'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('date');
            $table->index('reference');
            $table->index('status');
            $table->index('type');
            $table->index('created_by');
            $table->index(['date', 'status']);
        });

        Schema::create('finance_journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('finance_journal_entries')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('finance_accounts')->onDelete('restrict');
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0)->comment('Debit amount');
            $table->decimal('credit', 15, 2)->default(0)->comment('Credit amount');
            $table->timestamps();

            $table->index('journal_entry_id');
            $table->index('account_id');
            $table->index(['journal_entry_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_journal_entry_lines');
        Schema::dropIfExists('finance_journal_entries');
    }
};
