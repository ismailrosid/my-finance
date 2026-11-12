<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained('debts')->onDelete('cascade');
            $table->unsignedSmallInteger('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 16, 2)->default(0.00);
            $table->decimal('principal_amount', 16, 2)->default(0.00);
            $table->decimal('interest_amount', 16, 2)->default(0.00);
            $table->decimal('fee_amount', 16, 2)->default(0.00);
            $table->decimal('paid_amount', 16, 2)->default(0.00);
            $table->string('status', 15)->default('unpaid'); // unpaid, partially_paid, paid, overdue
            $table->date('payment_date')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['debt_id', 'installment_number']);
            $table->index(['debt_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};

