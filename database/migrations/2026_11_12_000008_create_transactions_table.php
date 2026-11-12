<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('transaction_code', 40)->unique();
            $table->string('transaction_type', 20); // income, expense, transfer, debt_payment
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('restrict');
            $table->unsignedBigInteger('source_account_id')->nullable();
            $table->unsignedBigInteger('destination_account_id')->nullable();
            $table->decimal('amount', 16, 2)->default(0.00);
            $table->decimal('fee_amount', 16, 2)->default(0.00);
            $table->date('transaction_date');
            $table->time('transaction_time')->nullable();
            $table->string('transaction_status', 15)->default('completed'); // completed, pending, cancelled
            $table->boolean('is_expected')->default(false);
            $table->unsignedBigInteger('debt_id')->nullable();
            $table->unsignedBigInteger('installment_id')->nullable();
            $table->string('description', 255)->default('');
            $table->text('notes')->nullable();
            $table->string('attachment_url', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('source_account_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->foreign('destination_account_id')->references('id')->on('accounts')->onDelete('restrict');

            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'transaction_type']);
            $table->index(['user_id', 'transaction_status']);
            $table->index('source_account_id');
            $table->index('destination_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

