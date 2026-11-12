<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->unsignedTinyInteger('period_month'); // 1–12
            $table->unsignedSmallInteger('period_year');
            $table->decimal('amount_limit', 16, 2)->default(0.00);
            $table->unsignedTinyInteger('alert_threshold_percentage')->default(80);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'period_month', 'period_year']);
            $table->index(['user_id', 'period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};

