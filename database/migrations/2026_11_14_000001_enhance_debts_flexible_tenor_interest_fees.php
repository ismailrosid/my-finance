<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->unsignedInteger('tenor_value')->default(1)->after('tenor_months');
            $table->string('tenor_unit', 10)->default('months')->after('tenor_value'); // days, weeks, months, years
            $table->string('interest_period', 15)->default('annual')->after('interest_rate'); // daily, monthly, annual
            $table->string('interest_method', 15)->default('flat')->after('interest_period'); // flat, effective, compound
            $table->string('admin_fee_type', 15)->default('fixed')->after('fee_amount'); // fixed, percentage
            $table->decimal('admin_fee_value', 16, 2)->default(0.00)->after('admin_fee_type');
            $table->decimal('interest_amount', 16, 2)->default(0.00)->after('admin_fee_value');
            $table->string('repayment_frequency', 15)->default('monthly')->after('tenor_unit'); // single, daily, weekly, monthly, annually
        });

        // Migrate existing debt records to ensure backward compatibility
        DB::table('debts')->update([
            'tenor_value'         => DB::raw('COALESCE(tenor_months, 1)'),
            'tenor_unit'          => 'months',
            'interest_period'     => 'annual',
            'interest_method'     => 'flat',
            'admin_fee_type'      => 'fixed',
            'admin_fee_value'     => DB::raw('COALESCE(fee_amount, 0)'),
            'repayment_frequency' => 'monthly',
        ]);

        // Audit table columns snapshot sync
        if (Schema::hasTable('debts_aud')) {
            Schema::table('debts_aud', function (Blueprint $table) {
                $table->unsignedInteger('tenor_value')->nullable()->after('tenor_months');
                $table->string('tenor_unit', 10)->nullable()->after('tenor_value');
                $table->string('interest_period', 15)->nullable()->after('interest_rate');
                $table->string('interest_method', 15)->nullable()->after('interest_period');
                $table->string('admin_fee_type', 15)->nullable()->after('fee_amount');
                $table->decimal('admin_fee_value', 16, 2)->nullable()->after('admin_fee_type');
                $table->decimal('interest_amount', 16, 2)->nullable()->after('admin_fee_value');
                $table->string('repayment_frequency', 15)->nullable()->after('tenor_unit');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('debts_aud')) {
            Schema::table('debts_aud', function (Blueprint $table) {
                $table->dropColumn([
                    'tenor_value',
                    'tenor_unit',
                    'repayment_frequency',
                    'interest_period',
                    'interest_method',
                    'admin_fee_type',
                    'admin_fee_value',
                    'interest_amount',
                ]);
            });
        }

        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn([
                'tenor_value',
                'tenor_unit',
                'repayment_frequency',
                'interest_period',
                'interest_method',
                'admin_fee_type',
                'admin_fee_value',
                'interest_amount',
            ]);
        });
    }
};
