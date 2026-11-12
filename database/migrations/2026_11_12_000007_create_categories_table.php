<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('category_name', 100);
            $table->string('category_type', 10); // income, expense
            $table->string('color', 20)->nullable()->default('#10B981');
            $table->string('icon', 50)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'category_type', 'is_active']);
            $table->index('is_system');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

