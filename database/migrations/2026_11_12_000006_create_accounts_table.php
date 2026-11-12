<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('account_name', 100);
            $table->string('account_number', 50)->nullable();
            $table->string('account_type', 30)->comment('bank, e_wallet, cash, credit_card, investment, other');
            $table->string('currency_code', 3)->default('IDR');
            $table->decimal('initial_balance', 16, 2)->default(0.00);
            $table->decimal('current_balance', 16, 2)->default(0.00);
            $table->string('color', 20)->nullable()->default('#10B981');
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'account_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};

