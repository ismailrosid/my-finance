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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number', 25)->nullable()->after('password');
            $table->string('avatar_url', 255)->nullable()->after('phone_number');
            $table->boolean('is_active')->default(true)->after('avatar_url')->index();
            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'avatar_url', 'is_active', 'deleted_at']);
        });
    }
};

