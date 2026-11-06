<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update default role value to storekeeper
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('storekeeper')->change();
        });

        // 2. Map existing user roles to target role keys
        DB::table('users')->where('role', 'admin')->update(['role' => 'administrator']);
        DB::table('users')->where('role', 'supervisor')->update(['role' => 'warehouse_manager']);
        DB::table('users')->where('role', 'user')->update(['role' => 'storekeeper']);
        // dispatch_officer remains dispatch_officer
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Revert target roles back to legacy keys
        DB::table('users')->where('role', 'administrator')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'warehouse_manager')->update(['role' => 'supervisor']);
        DB::table('users')->where('role', 'storekeeper')->update(['role' => 'user']);

        // 2. Revert default role value to user
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('user')->change();
        });
    }
};
