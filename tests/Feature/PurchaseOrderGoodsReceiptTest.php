<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class PurchaseOrderGoodsReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_posts_exact_stock_once_and_updates_purchase_order_outstanding(): void
    {
        $user = User::factory()->warehouseManager()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();
        $receiving = $this->createReceivingArea();

        $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'order_date' => today()->toDateString(),
            'expected_date' => today()->addDays(10)->toDateString(),
            'notes' => 'Quarterly order',
            'lines' => [[
                'product_id' => $product->id,
                'ordered_quantity' => '10.000',
            ]],
        ])->assertRedirect();

        $purchaseOrder = PurchaseOrder::query()->with('lines')->firstOrFail();

        $this->assertMatchesRegularExpression('/\APO-\d{4}-\d+\z/', $purchaseOrder->order_number);
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $purchaseOrder->status);

        $this->post(route('purchase-orders.approve', $purchaseOrder))
            ->assertRedirect(route('purchase-orders.show', $purchaseOrder));

        $this->assertSame(PurchaseOrder::STATUS_APPROVED, $purchaseOrder->fresh()->status);

        $review = $this->startReceipt($purchaseOrder, $product, $receiving, '4.250', 'BATCH-A');
        $token = $this->reviewToken($review);

        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        $this->post(route('goods-receipts.confirm', $token))->assertRedirect();

        $receipt = GoodsReceipt::query()->where('submission_token', $token)->firstOrFail();
        $batch = Batch::query()
            ->where('product_id', $product->id)
            ->where('batch_number', 'BATCH-A')
            ->firstOrFail();

        $this->assertSame('confirmed', $receipt->status);
        $this->assertSame($user->id, $receipt->received_by);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => GoodsReceipt::class,
            'auditable_id' => $receipt->id,
            'event' => 'confirmed',
            'user_id' => $user->id,
        ]);

        $this->assertSame(PurchaseOrder::STATUS_PARTIALLY_RECEIVED, $purchaseOrder->fresh()->status);
        $this->assertSame('5.750', $purchaseOrder->lines()->firstOrFail()->outstandingQuantity());

        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $receiving->id,
            'quantity' => '4.250',
        ]);

        $this->assertDatabaseHas('bin_inventory', [
            'location_id' => $receiving->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH-A',
            'quantity' => '4.250',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'batch_id' => $batch->id,
            'movement_type' => 'receipt',
            'quantity' => '4.250',
            'performed_by' => $user->id,
        ]);

        $this->post(route('goods-receipts.confirm', $token))->assertStatus(409);

        $this->assertSame(1, GoodsReceipt::query()->where('submission_token', $token)->count());
        $this->assertSame(1, StockMovement::query()->where('reference_id', $receipt->id)->count());

        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $receiving->id,
            'quantity' => '4.250',
        ]);

        $finalReview = $this->startReceipt(
            $purchaseOrder->fresh(),
            $product,
            $receiving,
            '5.750',
            'BATCH-A'
        );

        $this->post(route('goods-receipts.confirm', $this->reviewToken($finalReview)))
            ->assertRedirect();

        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $purchaseOrder->fresh()->status);
        $this->assertSame('0.000', $purchaseOrder->lines()->firstOrFail()->outstandingQuantity());

        $this->assertSame(
            1,
            Batch::query()
                ->where('product_id', $product->id)
                ->where('batch_number', 'BATCH-A')
                ->count()
        );

        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $receiving->id,
            'quantity' => '10.000',
        ]);
    }

    public function test_direct_receipt_requires_review_and_posts_batch_dates_and_receiving_details(): void
    {
        $user = User::factory()->storekeeper()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();
        $receiving = $this->createReceivingArea();

        $response = $this->post(route('goods-receipts.review'), [
            'supplier_id' => $supplier->id,
            'notes' => 'Direct import',
            'lines' => [[
                'product_id' => $product->id,
                'quantity' => '3.125',
                'batch_number' => 'DIRECT-1',
                'manufactured_at' => today()->subDays(5)->toDateString(),
                'expires_at' => today()->addDays(30)->toDateString(),
                'location_id' => $receiving->id,
            ]],
        ]);

        $token = $this->reviewToken($response);

        $response->assertRedirect(route('goods-receipts.review.show', $token));

        $this->assertDatabaseCount('goods_receipts', 0);

        $this->get(route('goods-receipts.review.show', $token))
            ->assertOk()
            ->assertSee('Review receipt before posting')
            ->assertSee('DIRECT-1');

        $this->post(route('goods-receipts.confirm', $token))->assertRedirect();

        $receipt = GoodsReceipt::query()->where('submission_token', $token)->firstOrFail();
        $line = $receipt->lines()->with('batch')->firstOrFail();

        $this->assertNull($receipt->purchase_order_id);
        $this->assertSame($supplier->id, $receipt->supplier_id);
        $this->assertSame($user->id, $receipt->received_by);
        $this->assertNotNull($receipt->received_at);

        $this->assertSame(
            today()->subDays(5)->toDateString(),
            $line->batch->manufactured_at->toDateString()
        );

        $this->assertSame(
            today()->addDays(30)->toDateString(),
            $line->batch->expires_at->toDateString()
        );

        $this->assertSame('30 days', $line->batch->expiry_status);
    }

    public function test_failed_stock_movement_rolls_back_receipt_batch_and_balance(): void
    {
        $user = User::factory()->storekeeper()->create();
        $product = Product::factory()->create();
        $receiving = $this->createReceivingArea();
        $token = (string) Str::uuid();

        StockMovement::creating(function (): never {
            throw new RuntimeException('Simulated stock movement write failure.');
        });

        try {
            app(InventoryService::class)->receive([
                'supplier_id' => null,
                'lines' => [[
                    'product_id' => $product->id,
                    'quantity' => '7.125',
                    'batch_number' => 'ROLLBACK-1',
                    'manufactured_at' => null,
                    'expires_at' => null,
                    'location_id' => $receiving->id,
                ]],
            ], $user, $token);

            $this->fail('The simulated stock movement failure should be raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated stock movement write failure.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing('goods_receipts', ['submission_token' => $token]);

        $this->assertDatabaseMissing('batches', [
            'product_id' => $product->id,
            'batch_number' => 'ROLLBACK-1',
        ]);

        $this->assertDatabaseMissing('stock_balances', [
            'location_id' => $receiving->id,
        ]);

        $this->assertDatabaseMissing('bin_inventory', [
            'location_id' => $receiving->id,
            'batch_number' => 'ROLLBACK-1',
        ]);

        $this->assertDatabaseMissing('stock_movements', [
            'reference_type' => 'goods_receipt',
        ]);
    }

    public function test_purchase_order_receipt_rejects_unlisted_products_and_unacknowledged_overages(): void
    {
        $this->actingAs(User::factory()->storekeeper()->create());

        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();
        $purchaseOrder = $this->createApprovedPurchaseOrder($product, '2.000');
        $receiving = $this->createReceivingArea();

        $this->post(route('goods-receipts.review'), [
            'purchase_order_id' => $purchaseOrder->id,
            'lines' => [[
                'product_id' => $otherProduct->id,
                'quantity' => '1.000',
                'batch_number' => 'OTHER',
                'location_id' => $receiving->id,
            ]],
        ])->assertSessionHasErrors('lines');

        $this->post(route('goods-receipts.review'), [
            'purchase_order_id' => $purchaseOrder->id,
            'lines' => [[
                'product_id' => $product->id,
                'quantity' => '2.125',
                'batch_number' => 'OVER',
                'location_id' => $receiving->id,
            ]],
        ])->assertSessionHasErrors('accept_discrepancies');

        $this->assertDatabaseCount('goods_receipts', 0);
    }

    public function test_purchase_order_rejects_duplicate_products_and_only_drafts_can_be_edited(): void
    {
        $this->actingAs(User::factory()->warehouseManager()->create());

        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $orderData = [
            'supplier_id' => $supplier->id,
            'order_date' => today()->toDateString(),
            'lines' => [
                ['product_id' => $product->id, 'ordered_quantity' => '2.000'],
                ['product_id' => $product->id, 'ordered_quantity' => '3.000'],
            ],
        ];

        $this->post(route('purchase-orders.store'), $orderData)
            ->assertSessionHasErrors('lines');

        $this->assertDatabaseCount('purchase_orders', 0);

        $orderData['lines'] = [[
            'product_id' => $product->id,
            'ordered_quantity' => '2.000',
        ]];

        $this->post(route('purchase-orders.store'), $orderData)->assertRedirect();

        $purchaseOrder = PurchaseOrder::query()->with('lines')->firstOrFail();

        $orderData['lines'][0]['ordered_quantity'] = '5.000';

        $this->put(route('purchase-orders.update', $purchaseOrder), $orderData)
            ->assertRedirect(route('purchase-orders.show', $purchaseOrder));

        $this->assertSame(
            '5.000',
            $purchaseOrder->lines()->firstOrFail()->ordered_quantity
        );

        $this->post(route('purchase-orders.approve', $purchaseOrder))->assertRedirect();

        $this->put(route('purchase-orders.update', $purchaseOrder), $orderData)
            ->assertSessionHasErrors('status');

        $this->patch(route('purchase-orders.cancel', $purchaseOrder))
            ->assertRedirect(route('purchase-orders.show', $purchaseOrder));

        $this->assertSame(
            PurchaseOrder::STATUS_CANCELLED,
            $purchaseOrder->fresh()->status
        );
    }

    public function test_acknowledged_over_receipt_is_recorded_and_caps_outstanding_at_zero(): void
    {
        $this->actingAs(User::factory()->storekeeper()->create());

        $product = Product::factory()->create();
        $purchaseOrder = $this->createApprovedPurchaseOrder($product, '2.000');
        $receiving = $this->createReceivingArea();

        $review = $this->post(route('goods-receipts.review'), [
            'purchase_order_id' => $purchaseOrder->id,
            'accept_discrepancies' => '1',
            'lines' => [[
                'product_id' => $product->id,
                'quantity' => '2.500',
                'batch_number' => 'OVER-ACK',
                'location_id' => $receiving->id,
            ]],
        ]);

        $token = $this->reviewToken($review);

        $this->get(route('goods-receipts.review.show', $token))
            ->assertOk()
            ->assertSee('exceeds the current PO outstanding amount');

        $this->post(route('goods-receipts.confirm', $token))->assertRedirect();

        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $purchaseOrder->fresh()->status);
        $this->assertSame('0.000', $purchaseOrder->lines()->firstOrFail()->outstandingQuantity());
    }

    public function test_guests_cannot_create_or_view_goods_receipts_or_stock(): void
    {
        $this->get(route('goods-receipts.index'))->assertRedirect(route('login'));
        $this->get(route('inventory.index'))->assertRedirect(route('login'));
        $this->post(route('goods-receipts.review'), [])->assertRedirect(route('login'));
        $this->get(route('goods-receipts.review.show', Str::uuid()))
            ->assertRedirect(route('login'));
    }

    public function test_receipt_rejects_inactive_destination_bin_and_ancestors(): void
    {
        $this->actingAs(User::factory()->storekeeper()->create());

        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        $receiving = $this->createReceivingArea();

        $receiving->parent->update(['status' => 'inactive']);

        $this->post(route('goods-receipts.review'), [
            'supplier_id' => $supplier->id,
            'lines' => [[
                'product_id' => $product->id,
                'quantity' => '1',
                'batch_number' => 'INACTIVE',
                'location_id' => $receiving->id,
            ]],
        ])->assertSessionHasErrors('lines.0.location_id');

        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('stock_balances', 0);
    }

    public function test_put_away_moves_exact_quantity_and_rejects_inactive_destination(): void
    {
        $this->actingAs(User::factory()->storekeeper()->create());

        $product = Product::factory()->create();
        $source = $this->createReceivingArea();
        $destination = $this->createBin('A03-B02');
        $supplier = Supplier::factory()->create();

        $response = $this->post(route('goods-receipts.review'), [
            'supplier_id' => $supplier->id,
            'lines' => [[
                'product_id' => $product->id,
                'quantity' => '8.250',
                'batch_number' => 'MOVE-1',
                'location_id' => $source->id,
            ]],
        ]);

        $this->post(route('goods-receipts.confirm', $this->reviewToken($response)))
            ->assertRedirect();

        $balance = StockBalance::query()
            ->with('batch')
            ->where('location_id', $source->id)
            ->firstOrFail();

        $this->post(route('stock-balances.put-away', $balance), [
            'destination_location_id' => $destination->id,
            'quantity' => '3.125',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $balance->batch_id,
            'location_id' => $source->id,
            'quantity' => '5.125',
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $balance->batch_id,
            'location_id' => $destination->id,
            'quantity' => '3.125',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => 'put_away',
            'from_location_id' => $source->id,
            'to_location_id' => $destination->id,
            'quantity' => '3.125',
        ]);

        $this->assertDatabaseHas('bin_inventory', [
            'location_id' => $source->id,
            'product_id' => $product->id,
            'batch_number' => 'MOVE-1',
            'quantity' => '5.125',
        ]);

        $this->assertDatabaseHas('bin_inventory', [
            'location_id' => $destination->id,
            'product_id' => $product->id,
            'batch_number' => 'MOVE-1',
            'quantity' => '3.125',
        ]);

        $destination->parent->parent->update(['status' => 'inactive']);

        $this->post(route('stock-balances.put-away', $balance), [
            'destination_location_id' => $destination->id,
            'quantity' => '1.000',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('destination_location_id');
    }

    public function test_batch_expiry_status_uses_expired_then_30_60_90_day_bands(): void
    {
        $product = Product::factory()->create();

        $cases = [
            [-1, 'Expired'],
            [0, '30 days'],
            [30, '30 days'],
            [31, '60 days'],
            [60, '60 days'],
            [61, '90 days'],
            [90, '90 days'],
            [91, 'Good'],
            [null, 'Good'],
        ];

        foreach ($cases as $index => [$days, $expected]) {
            $batch = Batch::query()->create([
                'product_id' => $product->id,
                'batch_number' => 'STATUS-'.$index,
                'expires_at' => $days === null
                    ? null
                    : today()->addDays($days)->toDateString(),
            ]);

            $this->assertSame($expected, $batch->expiry_status);
        }
    }

    public function test_goods_receipt_history_and_print_view_are_searchable(): void
    {
        $user = User::factory()->storekeeper()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();
        $receiving = $this->createReceivingArea();

        $response = $this->post(route('goods-receipts.review'), [
            'supplier_id' => $supplier->id,
            'lines' => [[
                'product_id' => $product->id,
                'quantity' => '1.000',
                'batch_number' => 'SEARCHABLE-BATCH',
                'location_id' => $receiving->id,
            ]],
        ]);

        $this->post(route('goods-receipts.confirm', $this->reviewToken($response)))
            ->assertRedirect();

        $receipt = GoodsReceipt::query()->firstOrFail();

        $this->get(route('goods-receipts.index', ['search' => $receipt->receipt_number]))
            ->assertOk()
            ->assertSee($receipt->receipt_number)
            ->assertSee($supplier->name);

        $this->get(route('goods-receipts.print', $receipt))
            ->assertOk()
            ->assertSee($receipt->receipt_number)
            ->assertSee('SEARCHABLE-BATCH');
    }

    public function test_procurement_and_inventory_pages_render(): void
    {
        $this->actingAs(User::factory()->warehouseManager()->create());

        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();
        $receiving = $this->createReceivingArea();

        $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'order_date' => today()->toDateString(),
            'lines' => [[
                'product_id' => $product->id,
                'ordered_quantity' => '5.000',
            ]],
        ])->assertRedirect();

        $purchaseOrder = PurchaseOrder::query()->firstOrFail();

        $this->get(route('purchase-orders.index'))
            ->assertOk()
            ->assertSee($purchaseOrder->order_number);

        $this->get(route('purchase-orders.create'))
            ->assertOk()
            ->assertSee('Create purchase order');

        $this->get(route('purchase-orders.show', $purchaseOrder))
            ->assertOk()
            ->assertSee('Outstanding');

        $this->get(route('purchase-orders.edit', $purchaseOrder))
            ->assertOk()
            ->assertSee('Save as draft');

        $this->get(route('purchase-orders.print', $purchaseOrder))
            ->assertOk()
            ->assertSee('Purchase Order');

        $this->get(route('goods-receipts.create', ['location_id' => $receiving->id]))
            ->assertOk()
            ->assertSee($receiving->code);

        $this->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('Stock and batches');
    }

    private function startReceipt(
        PurchaseOrder $purchaseOrder,
        Product $product,
        Location $receiving,
        string $quantity,
        string $batchNumber,
    ): TestResponse {
        return $this->post(route('goods-receipts.review'), [
            'purchase_order_id' => $purchaseOrder->id,
            'lines' => [[
                'product_id' => $product->id,
                'quantity' => $quantity,
                'batch_number' => $batchNumber,
                'location_id' => $receiving->id,
            ]],
        ]);
    }

    private function reviewToken(TestResponse $response): string
    {
        $this->assertNotNull($location = $response->headers->get('Location'));

        preg_match('/goods-receipts\/review\/([0-9a-f-]{36})/', $location, $matches);

        $this->assertArrayHasKey(1, $matches);

        return $matches[1];
    }

    private function createApprovedPurchaseOrder(
        Product $product,
        string $quantity
    ): PurchaseOrder {
        $creator = User::query()->first();

        if (! $creator) {
            $creator = User::factory()->warehouseManager()->create();
        }

        $purchaseOrder = PurchaseOrder::query()->create([
            'order_number' => 'PO-TEST-'.uniqid(),
            'supplier_id' => Supplier::factory()->create()->id,
            'order_date' => today(),
            'status' => PurchaseOrder::STATUS_APPROVED,
            'created_by' => $creator->id,
        ]);

        PurchaseOrderLine::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'ordered_quantity' => $quantity,
            'received_quantity' => '0.000',
        ]);

        return $purchaseOrder;
    }

    private function createBin(string $code = 'A03-B01'): Location
    {
        $warehouse = Location::query()->create([
            'type' => 'warehouse',
            'name' => 'Namanve Warehouse',
            'status' => 'active',
        ]);

        $zone = Location::query()->create([
            'parent_id' => $warehouse->id,
            'type' => 'zone',
            'name' => 'Zone A03',
            'status' => 'active',
        ]);

        $rack = Location::query()->create([
            'parent_id' => $zone->id,
            'type' => 'rack',
            'name' => 'Rack 01',
            'status' => 'active',
        ]);

        return Location::query()->create([
            'parent_id' => $rack->id,
            'type' => 'bin',
            'name' => 'Bin '.$code,
            'code' => $code,
            'status' => 'active',
        ]);
    }

    private function createReceivingArea(string $name = 'Receiving Area'): Location
    {
        $warehouse = Location::query()->create([
            'type' => 'warehouse',
            'name' => 'Namanve Warehouse',
            'status' => 'active',
        ]);

        return Location::query()->create([
            'parent_id' => $warehouse->id,
            'type' => 'receiving',
            'name' => $name,
            'code' => 'RCV-'.strtoupper(Str::random(6)),
            'status' => 'active',
        ]);
    }
}
