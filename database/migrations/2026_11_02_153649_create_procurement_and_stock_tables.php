
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->nullable()->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['supplier_id', 'status']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('ordered_quantity', 12, 3)->unsigned();
            $table->decimal('received_quantity', 12, 3)->unsigned()->default(0);
            $table->timestamps();
            $table->unique(['purchase_order_id', 'product_id']);
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->nullable()->unique();
            $table->uuid('submission_token')->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 16)->default('confirmed')->index();
            $table->text('notes')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['supplier_id', 'received_at']);
            $table->index(['purchase_order_id', 'received_at']);
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('batch_number', 100);
            $table->date('manufactured_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'batch_number']);
            $table->index('expires_at');
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->decimal('quantity', 12, 3)->unsigned();
            $table->timestamps();
            $table->index(['product_id', 'created_at']);
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->decimal('quantity', 12, 3)->unsigned();
            $table->timestamps();
            $table->unique(['batch_id', 'location_id']);
            $table->index('location_id');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->foreignId('goods_receipt_line_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('movement_type', 24);
            $table->decimal('quantity', 12, 3)->unsigned();
            $table->string('reference_type', 32)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->timestamp('moved_at');
            $table->timestamps();
            $table->index(['batch_id', 'moved_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        $this->migrateExistingBinInventory();
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }

    private function migrateExistingBinInventory(): void
    {
        DB::table('bin_inventory')->orderBy('id')->each(function (object $inventory): void {
            $batch = DB::table('batches')
                ->where('product_id', $inventory->product_id)
                ->where('batch_number', $inventory->batch_number)
                ->first();

            $batchId = $batch?->id ?? DB::table('batches')->insertGetId([
                'product_id' => $inventory->product_id,
                'batch_number' => $inventory->batch_number,
                'created_at' => $inventory->created_at,
                'updated_at' => $inventory->updated_at,
            ]);

            DB::table('stock_balances')->insert([
                'batch_id' => $batchId,
                'location_id' => $inventory->location_id,
                'quantity' => $inventory->quantity,
                'created_at' => $inventory->created_at,
                'updated_at' => $inventory->updated_at,
            ]);

            DB::table('stock_movements')->insert([
                'batch_id' => $batchId,
                'to_location_id' => $inventory->location_id,
                'movement_type' => 'opening_balance',
                'quantity' => $inventory->quantity,
                'reference_type' => 'bin_inventory',
                'reference_id' => $inventory->id,
                'moved_at' => $inventory->created_at,
                'created_at' => $inventory->created_at,
                'updated_at' => $inventory->updated_at,
            ]);
        });
    }
};
