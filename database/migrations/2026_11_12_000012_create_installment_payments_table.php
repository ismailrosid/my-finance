<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_id')->constrained('installments')->onDelete('cascade');
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('restrict');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict');
            $table->date('payment_date');
            $table->decimal('amount_paid', 16, 2)->default(0.00);
            $table->decimal('principal_paid', 16, 2)->default(0.00);
            $table->decimal('interest_paid', 16, 2)->default(0.00);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index('installment_id');
            $table->index('transaction_id');
            $table->index('account_id');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_payments');
    }
};

