<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan 02 (aero-core) Task 5 of foundation 10/10 push.
 *
 * Creates the feedback_items table that HelpSupportController has been
 * trying to query / insert / increment votes on. Without this migration
 * the /admin/help/feedback routes throw "Table 'feedback_items' doesn't exist".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->text('description');
            $table->enum('type', ['feature', 'bug', 'improvement'])->default('feature');
            $table->enum('status', ['open', 'planned', 'in_progress', 'shipped', 'declined'])->default('open');
            $table->unsignedInteger('votes')->default(0);
            $table->text('response')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'votes']);
            $table->index(['type', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_items');
    }
};
