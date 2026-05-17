<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_leave_balances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $t->unsignedSmallInteger('year');
            $t->decimal('entitled', 6, 2)->default(0);
            $t->decimal('used', 6, 2)->default(0);
            $t->decimal('carried_forward', 6, 2)->default(0);
            $t->decimal('encashed', 6, 2)->default(0);
            $t->timestamps();
            $t->unique(['employee_id', 'leave_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_leave_balances');
    }
};
