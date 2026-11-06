<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::table('inventory_settings', function (Blueprint $table) {
            $table->string('company_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('expiry_windows')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_settings', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'logo_path', 'expiry_windows']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
