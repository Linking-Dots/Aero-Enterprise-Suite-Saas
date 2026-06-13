<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the tenant_invitations table for team member invitation system.
     * This table stores pending invitations for new team members.
     */
    public function up(): void
    {
        Schema::create('tenant_invitations', function (Blueprint $table) {
            $table->id();

            // Email address being invited (indexed for quick lookup)
            $table->string('email')->index();

            // Unique secure token for invitation acceptance (32 character random string)
            $table->string('token', 64)->unique();

            // Role to be assigned upon acceptance (references Spatie role name)
            $table->string('role')->default('employee');

            // Foreign key to the user who sent the invitation
            $table->foreignId('invited_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Invitation expiration timestamp (default 7 days from creation)
            $table->timestamp('expires_at')->nullable();

            // Track if invitation was accepted
            $table->timestamp('accepted_at')->nullable();

            // Track if invitation was cancelled
            $table->timestamp('cancelled_at')->nullable();

            // Optional metadata for additional context (department, designation, etc.)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Composite index for common queries
            $table->index(['email', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_invitations');
    }
};
