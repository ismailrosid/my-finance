<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->string('item_name', 150);
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->string('unit_name', 20)->nullable()->default('pcs');
            $table->decimal('unit_price', 16, 2)->default(0.00);
            $table->decimal('total_price', 16, 2)->default(0.00);
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};

