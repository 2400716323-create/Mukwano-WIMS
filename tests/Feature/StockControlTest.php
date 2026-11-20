<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BinInventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\StockReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class StockControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_debits_and_credits_exact_quantity_with_reason_actor_and_date(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [$product, $batch, $source, $destination, $balance] = $this->createStock('10.000');
        $transferDate = now()->subHour()->startOfMinute();

        $this->post(route('stock-transfers.store', $balance), [
            'destination_location_id' => $destination->id,
            'quantity' => '3.125',
            'reason' => 'Rebalance picking stock',
            'transferred_at' => $transferDate->format('Y-m-d\TH:i'),
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect(route('stock-transfers.create'));

        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $source->id,
            'quantity' => '6.875',
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $destination->id,
            'quantity' => '3.125',
        ]);
        $this->assertDatabaseHas('bin_inventory', [
            'location_id' => $source->id,
            'product_id' => $product->id,
            'batch_number' => $batch->batch_number,
            'quantity' => '6.875',
        ]);
        $this->assertDatabaseHas('bin_inventory', [
            'location_id' => $destination->id,
            'product_id' => $product->id,
            'batch_number' => $batch->batch_number,
            'quantity' => '3.125',
        ]);

        $movement = StockMovement::query()->where('movement_type', 'transfer')->firstOrFail();
        $this->assertSame($source->id, $movement->from_location_id);
        $this->assertSame($destination->id, $movement->to_location_id);
        $this->assertSame('3.125', $movement->quantity);
        $this->assertSame('Rebalance picking stock', $movement->reason);
        $this->assertSame($user->id, $movement->performed_by);
        $this->assertSame($transferDate->format('Y-m-d H:i'), $movement->moved_at->format('Y-m-d H:i'));
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => StockMovement::class,
            'auditable_id' => $movement->id,
            'event' => 'stock_transferred',
            'user_id' => $user->id,
        ]);
        $this->assertTrue(app(StockReconciliationService::class)->reconcile()['matched']);
    }

    public function test_transfer_rejects_negative_or_over_available_stock_without_changes(): void
    {
        $this->actingAs(User::factory()->create());
        [, $batch, $source, $destination, $balance] = $this->createStock('2.000');

        foreach (['-1.000', '2.001'] as $quantity) {
            $this->post(route('stock-transfers.store', $balance), [
                'destination_location_id' => $destination->id,
                'quantity' => $quantity,
                'reason' => 'Invalid transfer',
                'transferred_at' => now()->format('Y-m-d\TH:i'),
                'idempotency_key' => (string) Str::uuid(),
            ])->assertSessionHasErrors('quantity');
        }

        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $source->id,
            'quantity' => '2.000',
        ]);
        $this->assertDatabaseMissing('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $destination->id,
        ]);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_transfer_rejects_inactive_destination_and_inactive_source(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [, $batch, $source, $destination, $balance] = $this->createStock('2.000');
        $destination->parent->update(['status' => 'inactive']);
        $this->post(route('stock-transfers.store', $balance), [
            'destination_location_id' => $destination->id,
            'quantity' => '1.000',
            'reason' => 'Inactive destination',
            'transferred_at' => now()->format('Y-m-d\TH:i'),
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('destination_location_id');

        $destination->parent->update(['status' => 'active']);
        $source->update(['status' => 'inactive']);
        $this->post(route('stock-transfers.store', $balance), [
            'destination_location_id' => $destination->id,
            'quantity' => '1.000',
            'reason' => 'Inactive source',
            'transferred_at' => now()->format('Y-m-d\TH:i'),
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('destination_location_id');

        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $source->id,
            'quantity' => '2.000',
        ]);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_transfer_rolls_back_both_balances_when_ledger_write_fails(): void
    {
        $user = User::factory()->create();
        [$product, $batch, $source, $destination, $balance] = $this->createStock('4.500');
        StockMovement::creating(function (StockMovement $movement): void {
            if ($movement->movement_type === 'transfer') {
                throw new RuntimeException('Simulated transfer ledger failure.');
            }
        });

        try {
            app(InventoryService::class)->transfer(
                $balance,
                $destination,
                '1.250',
                'Failure test',
                $user,
                now(),
                (string) Str::uuid(),
            );
            $this->fail('A ledger write failure must abort the transfer.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated transfer ledger failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $source->id,
            'quantity' => '4.500',
        ]);
        $this->assertDatabaseMissing('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $destination->id,
        ]);
        $this->assertDatabaseHas('bin_inventory', [
            'location_id' => $source->id,
            'product_id' => $product->id,
            'batch_number' => $batch->batch_number,
            'quantity' => '4.500',
        ]);
        $this->assertDatabaseMissing('bin_inventory', [
            'location_id' => $destination->id,
            'product_id' => $product->id,
            'batch_number' => $batch->batch_number,
        ]);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_transfer_reloads_locked_balance_to_prevent_stale_overdraw(): void
    {
        $user = User::factory()->create();
        [, $batch, $source, $destination, $staleBalance] = $this->createStock('10.000');
        $otherDestination = $this->createBin('A03-B03');
        $service = app(InventoryService::class);

        $service->transfer($staleBalance, $destination, '7.000', 'First concurrent transfer', $user, now(), (string) Str::uuid());

        try {
            $service->transfer($staleBalance, $otherDestination, '4.000', 'Stale second transfer', $user, now(), (string) Str::uuid());
            $this->fail('A stale transfer must not spend already-transferred stock.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }

        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $source->id,
            'quantity' => '3.000',
        ]);
        $this->assertDatabaseMissing('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $otherDestination->id,
        ]);
        $this->assertSame(2, StockMovement::query()->count());
        $this->assertTrue(app(StockReconciliationService::class)->reconcile()['matched']);
    }

    public function test_idempotency_key_blocks_repeated_transfer_submission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        [, $batch, $source, $destination, $balance] = $this->createStock('4.000');
        $idempotencyKey = (string) Str::uuid();
        $transfer = [
            'destination_location_id' => $destination->id,
            'quantity' => '1.000',
            'reason' => 'Repeated form submission',
            'transferred_at' => now()->format('Y-m-d\TH:i'),
            'idempotency_key' => $idempotencyKey,
        ];

        $this->post(route('stock-transfers.store', $balance), $transfer)->assertRedirect();
        $this->post(route('stock-transfers.store', $balance), $transfer)
            ->assertSessionHasErrors('destination_location_id');

        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $source->id,
            'quantity' => '3.000',
        ]);
        $this->assertSame(1, StockMovement::query()->where('idempotency_key', $idempotencyKey)->count());
    }

    public function test_reconciliation_compares_location_balances_to_signed_movement_totals(): void
    {
        [, $batch, $source, $destination, $balance] = $this->createStock('5.250');
        $service = app(StockReconciliationService::class);
        $this->assertSame([
            'matched' => true,
            'checked' => 1,
            'mismatches' => [],
        ], $service->reconcile());

        $user = User::factory()->create();
        app(InventoryService::class)->transfer(
            $balance,
            $destination,
            '2.125',
            'Reconciliation setup',
            $user,
            now(),
            (string) Str::uuid(),
        );
        $this->assertTrue($service->reconcile()['matched']);

        StockBalance::query()
            ->where('batch_id', $batch->id)
            ->where('location_id', $destination->id)
            ->update(['quantity' => '2.000']);
        $result = $service->reconcile();
        $this->assertFalse($result['matched']);
        $this->assertSame(2, $result['checked']);
        $this->assertSame([
            'batch_id' => $batch->id,
            'location_id' => $destination->id,
            'balance' => '2.000',
            'ledger' => '2.125',
        ], $result['mismatches'][0]);

        $this->assertSame(1, Artisan::call('stock:reconcile'));
        $this->assertStringContainsString('Stock reconciliation found mismatches', Artisan::output());
    }

    public function test_reconciliation_command_passes_for_a_consistent_ledger(): void
    {
        $this->createStock('1.000');

        $this->assertSame(0, Artisan::call('stock:reconcile'));
        $this->assertStringContainsString('Stock reconciliation passed', Artisan::output());
    }

    public function test_stock_overview_enquiry_ledger_and_transfer_pages_render_and_filter(): void
    {
        $this->actingAs(User::factory()->warehouseManager()->create());
        [$product, $batch, $source, $destination, $balance] = $this->createStock('5.000');

        $this->get(route('inventory.index'))
            ->assertOk()
            ->assertSee($product->sku)
            ->assertSee($batch->batch_number)
            ->assertSee('Stock overview by product');

        $this->get(route('inventory.enquiry', ['product_id' => $product->id]))
            ->assertOk()
            ->assertSee($source->code)
            ->assertSee('Opening Balance');

        $this->get(route('inventory.ledger', ['batch_id' => $batch->id, 'location_id' => $source->id]))
            ->assertOk()
            ->assertSee($batch->batch_number)
            ->assertSee($source->code);

        $this->get(route('stock-transfers.create'))
            ->assertOk()
            ->assertSee($destination->code)
            ->assertSee('Transfer date and time');
    }

    public function test_guests_cannot_access_stock_control_pages_or_submit_transfers(): void
    {
        [, , , $destination, $balance] = $this->createStock('1.000');
        $this->get(route('inventory.index'))->assertRedirect(route('login'));
        $this->get(route('inventory.enquiry'))->assertRedirect(route('login'));
        $this->get(route('inventory.ledger'))->assertRedirect(route('login'));
        $this->get(route('stock-transfers.create'))->assertRedirect(route('login'));

        $this->post(route('stock-transfers.store', $balance), [
            'destination_location_id' => $destination->id,
            'quantity' => '1.000',
            'reason' => 'Guest request',
            'transferred_at' => now()->format('Y-m-d\TH:i'),
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect(route('login'));
    }

    /**
     * @return array{Product, Batch, Location, Location, StockBalance}
     */
    private function createStock(string $quantity): array
    {
        $product = Product::factory()->create();
        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'BATCH-'.Str::random(8),
        ]);
        $source = $this->createBin('A03-B01');
        $destination = $this->createBin('A03-B02');
        $balance = StockBalance::query()->create([
            'batch_id' => $batch->id,
            'location_id' => $source->id,
            'quantity' => $quantity,
        ]);
        BinInventory::query()->create([
            'location_id' => $source->id,
            'product_id' => $product->id,
            'batch_number' => $batch->batch_number,
            'quantity' => $quantity,
        ]);
        StockMovement::query()->create([
            'batch_id' => $batch->id,
            'to_location_id' => $source->id,
            'movement_type' => 'opening_balance',
            'quantity' => $quantity,
            'reference_type' => 'test',
            'moved_at' => now(),
        ]);

        return [$product, $batch, $source, $destination, $balance];
    }

    private function createBin(string $code): Location
    {
        $warehouse = Location::query()->create([
            'type' => 'warehouse',
            'name' => 'Namanve Warehouse '.$code,
            'status' => 'active',
        ]);
        $zone = Location::query()->create([
            'parent_id' => $warehouse->id,
            'type' => 'zone',
            'name' => 'Zone A03 '.$code,
            'status' => 'active',
        ]);
        $rack = Location::query()->create([
            'parent_id' => $zone->id,
            'type' => 'rack',
            'name' => 'Rack 01 '.$code,
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
}
