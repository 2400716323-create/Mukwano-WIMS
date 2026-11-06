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
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('performed_by')->constrained('users')->nullOnDelete();
            $table->foreignId('stock_count_line_id')->nullable()->after('goods_receipt_line_id')->unique()
                ->constrained('stock_count_lines')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['stock_count_line_id']);
            $table->dropUnique(['stock_count_line_id']);
            $table->dropColumn(['approved_by', 'stock_count_line_id']);
        });
    }
};
