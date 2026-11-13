<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Revision Info Table
        Schema::create('revision_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('user_id');
            $table->index('created_at');
        });

        // 2. Transactions Shadow Audit Table
        Schema::create('transactions_aud', function (Blueprint $table) {
            $table->id('audit_id');
            $table->unsignedBigInteger('id'); // original transaction id
            $table->foreignId('revision_id')->constrained('revision_info')->onDelete('cascade');
            $table->unsignedTinyInteger('revision_type')->comment('0: CREATE, 1: UPDATE, 2: DELETE');

            // Original Columns Snapshot
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('transaction_code', 40)->nullable();
            $table->string('transaction_type', 20)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('source_account_id')->nullable();
            $table->unsignedBigInteger('destination_account_id')->nullable();
            $table->decimal('amount', 16, 2)->nullable();
            $table->decimal('fee_amount', 16, 2)->nullable();
            $table->date('transaction_date')->nullable();
            $table->time('transaction_time')->nullable();
            $table->string('transaction_status', 15)->nullable();
            $table->boolean('is_expected')->nullable();
            $table->unsignedBigInteger('debt_id')->nullable();
            $table->unsignedBigInteger('installment_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_url', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->index(['id', 'revision_id']);
            $table->index('revision_id');
            $table->index('revision_type');
            $table->index('user_id');
        });

        // 3. Accounts Shadow Audit Table
        Schema::create('accounts_aud', function (Blueprint $table) {
            $table->id('audit_id');
            $table->unsignedBigInteger('id'); // original account id
            $table->foreignId('revision_id')->constrained('revision_info')->onDelete('cascade');
            $table->unsignedTinyInteger('revision_type')->comment('0: CREATE, 1: UPDATE, 2: DELETE');

            // Original Columns Snapshot
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('account_name', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('account_type', 30)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('initial_balance', 16, 2)->nullable();
            $table->decimal('current_balance', 16, 2)->nullable();
            $table->string('color', 20)->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->index(['id', 'revision_id']);
            $table->index('revision_id');
            $table->index('revision_type');
            $table->index('user_id');
        });

        // 4. Debts Shadow Audit Table
        Schema::create('debts_aud', function (Blueprint $table) {
            $table->id('audit_id');
            $table->unsignedBigInteger('id'); // original debt id
            $table->foreignId('revision_id')->constrained('revision_info')->onDelete('cascade');
            $table->unsignedTinyInteger('revision_type')->comment('0: CREATE, 1: UPDATE, 2: DELETE');

            // Original Columns Snapshot
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('debt_code', 40)->nullable();
            $table->string('debt_type', 20)->nullable();
            $table->string('direction', 10)->nullable();
            $table->string('creditor_name', 150)->nullable();
            $table->decimal('principal_amount', 16, 2)->nullable();
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->decimal('fee_amount', 16, 2)->nullable();
            $table->decimal('total_amount', 16, 2)->nullable();
            $table->decimal('remaining_principal', 16, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('tenor_months')->nullable();
            $table->string('status', 15)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->index(['id', 'revision_id']);
            $table->index('revision_id');
            $table->index('revision_type');
            $table->index('user_id');
        });

        // 5. Budgets Shadow Audit Table
        Schema::create('budgets_aud', function (Blueprint $table) {
            $table->id('audit_id');
            $table->unsignedBigInteger('id'); // original budget id
            $table->foreignId('revision_id')->constrained('revision_info')->onDelete('cascade');
            $table->unsignedTinyInteger('revision_type')->comment('0: CREATE, 1: UPDATE, 2: DELETE');

            // Original Columns Snapshot
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedTinyInteger('period_month')->nullable();
            $table->unsignedSmallInteger('period_year')->nullable();
            $table->decimal('amount_limit', 16, 2)->nullable();
            $table->unsignedTinyInteger('alert_threshold_percentage')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['id', 'revision_id']);
            $table->index('revision_id');
            $table->index('revision_type');
            $table->index('user_id');
        });

        // 6. Categories Shadow Audit Table
        Schema::create('categories_aud', function (Blueprint $table) {
            $table->id('audit_id');
            $table->unsignedBigInteger('id'); // original category id
            $table->foreignId('revision_id')->constrained('revision_info')->onDelete('cascade');
            $table->unsignedTinyInteger('revision_type')->comment('0: CREATE, 1: UPDATE, 2: DELETE');

            // Original Columns Snapshot
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('category_name', 100)->nullable();
            $table->string('category_type', 10)->nullable();
            $table->string('color', 20)->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_system')->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->index(['id', 'revision_id']);
            $table->index('revision_id');
            $table->index('revision_type');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories_aud');
        Schema::dropIfExists('budgets_aud');
        Schema::dropIfExists('debts_aud');
        Schema::dropIfExists('accounts_aud');
        Schema::dropIfExists('transactions_aud');
        Schema::dropIfExists('revision_info');
    }
};
