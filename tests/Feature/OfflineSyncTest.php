<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BinInventory;
use App\Models\DispatchRequest;
use App\Models\Location;
use App\Models\OfflineSync;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_count_sync_is_idempotent_and_payload_conflicts_are_explicit(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $bin = $this->createBin();
        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'OFFLINE-COUNT-1',
        ]);
        StockBalance::query()->create([
            'batch_id' => $batch->id,
            'location_id' => $bin->id,
            'quantity' => '5.000',
        ]);
        $count = StockCount::query()->create([
            'location_id' => $bin->id,
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'created_by' => $user->id,
        ]);
        $line = StockCountLine::query()->create([
            'stock_count_id' => $count->id,
            'batch_id' => $batch->id,
            'location_id' => $bin->id,
            'system_quantity' => '5.000',
        ]);
        $this->actingAs($user);
        $clientId = (string) Str::uuid();
        $payload = [
            'client_id' => $clientId,
            'lines' => [
                $line->id => [
                    'physical_quantity' => '5.000',
                    'discrepancy_reason' => '',
                ],
            ],
        ];

        $this->postJson(route('offline.sync.count', $count), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'synced')
            ->assertJsonPath('duplicate', false);
        $this->postJson(route('offline.sync.count', $count), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'synced')
            ->assertJsonPath('duplicate', true);

        $this->assertSame('submitted', $count->fresh()->status);
        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $bin->id,
            'quantity' => '5.000',
        ]);
        $this->assertSame(1, OfflineSync::query()->where('id', $clientId)->count());
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => StockCount::class,
            'auditable_id' => $count->id,
            'event' => 'submitted',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseCount('audit_logs', 1);

        $payload['lines'][$line->id]['physical_quantity'] = '4.000';
        $this->postJson(route('offline.sync.count', $count), $payload)
            ->assertStatus(409)
            ->assertJsonPath('message', 'This client transaction ID was already used with different data or an account.');

        $this->assertSame('submitted', $count->fresh()->status);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_goods_receipt_sync_is_saved_for_review_only_once_and_server_validation_is_returned(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'active']);
        $supplier = Supplier::factory()->create(['status' => 'active']);
        $bin = $this->createBin();
        $this->actingAs($user);
        $clientId = (string) Str::uuid();
        $payload = [
            'client_id' => $clientId,
            'supplier_id' => $supplier->id,
            'notes' => 'Offline receipt draft',
            'lines' => [[
                'product_id' => $product->id,
                'quantity' => '2.500',
                'batch_number' => 'OFFLINE-GRN-1',
                'manufactured_at' => '',
                'expires_at' => '',
                'location_id' => $bin->id,
            ]],
        ];

        $this->postJson(route('offline.sync.goods-receipt'), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'synced')
            ->assertJsonPath('duplicate', false)
            ->assertJsonPath('next_url', route('goods-receipts.review.show', $clientId));
        $this->postJson(route('offline.sync.goods-receipt'), $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->assertSame(1, OfflineSync::query()->where('id', $clientId)->count());
        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('stock_balances', 0);

        $invalidPayload = $payload;
        $invalidPayload['client_id'] = (string) Str::uuid();
        $invalidPayload['lines'][0]['quantity'] = '0';
        $this->postJson(route('offline.sync.goods-receipt'), $invalidPayload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lines.0.quantity']);

        $this->assertSame(1, OfflineSync::query()->count());
        $this->assertDatabaseCount('goods_receipts', 0);
    }

    public function test_duplicate_dispatch_sync_does_not_debit_live_stock_twice(): void
    {
        $officer = User::factory()->create(['role' => 'dispatch_officer']);
        $product = Product::factory()->create();
        $bin = $this->createBin();
        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'OFFLINE-DISPATCH-1',
            'expires_at' => today()->addDays(30),
        ]);
        $balance = StockBalance::query()->create([
            'batch_id' => $batch->id,
            'location_id' => $bin->id,
            'quantity' => '5.000',
        ]);
        BinInventory::query()->create([
            'location_id' => $bin->id,
            'product_id' => $product->id,
            'batch_number' => $batch->batch_number,
            'quantity' => '5.000',
        ]);
        StockMovement::query()->create([
            'batch_id' => $batch->id,
            'to_location_id' => $bin->id,
            'movement_type' => 'opening_balance',
            'quantity' => '5.000',
            'reference_type' => 'test',
            'moved_at' => now(),
        ]);
        $dispatchRequest = DispatchRequest::query()->create([
            'product_id' => $product->id,
            'requested_quantity' => '2.000',
            'requested_by' => $officer->id,
            'requested_at' => now(),
        ]);
        $this->actingAs($officer);
        $clientId = (string) Str::uuid();
        $payload = [
            'client_id' => $clientId,
            'picks' => [$balance->id => '2.000'],
        ];

        $this->postJson(route('offline.sync.dispatch', $dispatchRequest), $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', false);
        $this->postJson(route('offline.sync.dispatch', $dispatchRequest), $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->assertSame('3.000', StockBalance::query()->where('batch_id', $batch->id)->firstOrFail()->quantity);
        $this->assertDatabaseCount('dispatch_lines', 1);
        $this->assertDatabaseCount('stock_movements', 2);

        $this->postJson(route('offline.sync.dispatch', $dispatchRequest), [
            'client_id' => (string) Str::uuid(),
            'picks' => [$balance->id => '2.000'],
        ])->assertForbidden();

        $this->assertSame('3.000', StockBalance::query()->where('batch_id', $batch->id)->firstOrFail()->quantity);
    }

    public function test_pwa_shell_and_offline_queue_are_registered_without_caching_authenticated_pages(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('profile.edit'))->assertOk();
        $response->assertSee('manifest.webmanifest');
        $response->assertSee('network-status');
        $response->assertSee('offline-queue');

        $serviceWorker = file_get_contents(public_path('service-worker.js'));
        $this->assertIsString($serviceWorker);
        $this->assertStringContainsString("request.mode === 'navigate'", $serviceWorker);
        $this->assertStringNotContainsString('cache.put(request', $serviceWorker);
    }

    private function createBin(): Location
    {
        $warehouse = Location::query()->create([
            'type' => 'warehouse',
            'name' => 'Offline Test Warehouse',
            'status' => 'active',
        ]);
        $zone = Location::query()->create([
            'parent_id' => $warehouse->id,
            'type' => 'zone',
            'name' => 'Offline Test Zone',
            'status' => 'active',
        ]);
        $rack = Location::query()->create([
            'parent_id' => $zone->id,
            'type' => 'rack',
            'name' => 'Offline Test Rack',
            'status' => 'active',
        ]);

        return Location::query()->create([
            'parent_id' => $rack->id,
            'type' => 'bin',
            'code' => 'OFFLINE-TEST-BIN',
            'name' => 'Offline Test Bin',
            'status' => 'active',
        ]);
    }
}
