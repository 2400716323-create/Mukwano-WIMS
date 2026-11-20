<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('put_away_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_number', 40)->unique();
            $table->foreignId('stock_balance_id')->nullable()->constrained('stock_balances')->nullOnDelete();
            $table->foreignId('batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_location_id')->constrained('locations')->restrictOnDelete();
            $table->decimal('quantity', 12, 3)->unsigned();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('assigned_to')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['assigned_to', 'status']);
            $table->index(['source_location_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('put_away_tasks');
    }
};
