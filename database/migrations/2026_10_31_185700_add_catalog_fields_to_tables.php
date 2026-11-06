<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->unique();
            $table->string('sap_material_number')->nullable()->unique();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('unit', 32);
            $table->decimal('min_level', 12, 3)->unsigned()->default(0);
            $table->decimal('reorder_level', 12, 3)->unsigned()->default(0);
            $table->decimal('max_level', 12, 3)->unsigned()->default(0);
            $table->string('status')->default('active')->index();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('name')->index();
            $table->string('code')->unique();
            $table->string('contact_person')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active')->index();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('event');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['auditable_type', 'auditable_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['auditable_type', 'auditable_id', 'event', 'old_values', 'new_values']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropIndex(['name']);
            $table->dropIndex(['status']);
            $table->dropColumn(['name', 'code', 'contact_person', 'phone', 'email', 'address', 'notes', 'status']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropUnique(['sku']);
            $table->dropUnique(['sap_material_number']);
            $table->dropIndex(['name']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'sku',
                'sap_material_number',
                'name',
                'description',
                'image_path',
                'unit',
                'min_level',
                'reorder_level',
                'max_level',
                'status',
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropIndex(['status']);
            $table->dropColumn(['name', 'description', 'status']);
        });
    }
};
