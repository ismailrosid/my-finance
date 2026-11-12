<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('debt_code', 40)->unique();
            $table->string('debt_type', 20); // platform_online, bank_loan, personal, credit_card, other
            $table->string('direction', 10)->default('payable'); // payable, receivable
            $table->string('creditor_name', 150);
            $table->decimal('principal_amount', 16, 2)->default(0.00);
            $table->decimal('interest_rate', 5, 2)->default(0.00);
            $table->decimal('fee_amount', 16, 2)->default(0.00);
            $table->decimal('total_amount', 16, 2)->default(0.00);
            $table->decimal('remaining_principal', 16, 2)->default(0.00);
            $table->date('start_date');
            $table->date('due_date');
            $table->unsignedInteger('tenor_months')->default(1);
            $table->string('status', 15)->default('active'); // active, paid_off, defaulted, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'due_date']);
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};

