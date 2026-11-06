<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->string('type', 16)->index();
            $table->string('name');
            $table->string('code', 80)->nullable()->unique();
            $table->string('status', 16)->default('active')->index();
            $table->timestamps();
            $table->index(['parent_id', 'type']);
        });

        Schema::create('bin_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('batch_number', 100);
            $table->decimal('quantity', 12, 3)->unsigned();
            $table->timestamps();
            $table->unique(['location_id', 'product_id', 'batch_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bin_inventory');
        Schema::dropIfExists('locations');
    }
};
