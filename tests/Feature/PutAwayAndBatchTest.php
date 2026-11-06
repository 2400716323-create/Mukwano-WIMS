<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BinInventory;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PutAwayAndBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_put_away_or_batches(): void
    {
        $this->get(route('put-away.index'))->assertRedirect(route('login'));
        $this->get(route('batches.index'))->assertRedirect(route('login'));
    }

    public function test_put_away_tasks_list_and_guided_assignment_form(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STOREKEEPER]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Mukwano Cooking Oil 5L',
            'sku' => 'OIL-5L-TEST',
        ]);

        $warehouse = Location::query()->create(['name' => 'Main Warehouse', 'code' => 'WH-PUTAWAY', 'type' => 'warehouse', 'status' => 'active']);
        $zone = Location::query()->create(['parent_id' => $warehouse->id, 'name' => 'Staging Zone', 'code' => 'ZN-PUTAWAY', 'type' => 'zone', 'status' => 'active']);
        $rack = Location::query()->create(['parent_id' => $zone->id, 'name' => 'Staging Rack', 'code' => 'RK-PUTAWAY', 'type' => 'rack', 'status' => 'active']);
        $receivingBin = Location::query()->create(['parent_id' => $rack->id, 'name' => 'Receiving Dock 1', 'code' => 'RCV-01', 'type' => 'bin', 'status' => 'active']);
        $storageBin = Location::query()->create(['parent_id' => $rack->id, 'name' => 'Storage A1', 'code' => 'BIN-A1', 'type' => 'bin', 'status' => 'active']);

        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'LOT-PUTAWAY-01',
            'manufactured_at' => today()->subMonth(),
            'expires_at' => today()->addMonths(6),
        ]);

        $balance = StockBalance::query()->create([
            'batch_id' => $batch->id,
            'location_id' => $receivingBin->id,
            'quantity' => '50.000',
        ]);

        $this->actingAs($user)
            ->get(route('put-away.index'))
            ->assertOk()
            ->assertSee('Put-away Tasks')
            ->assertSee('OIL-5L-TEST')
            ->assertSee('LOT-PUTAWAY-01')
            ->assertSee('RCV-01');

        $this->actingAs($user)
            ->get(route('put-away.show', $balance))
            ->assertOk()
            ->assertSee('Assign Destination Bin')
            ->assertSee('BIN-A1')
            ->assertSee('Confirm Put-away');
    }

    public function test_put_away_execution_moves_stock_atomically_to_destination_bin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STOREKEEPER]);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $warehouse = Location::query()->create(['name' => 'WH 1', 'code' => 'WH-01', 'type' => 'warehouse', 'status' => 'active']);
        $zone = Location::query()->create(['parent_id' => $warehouse->id, 'name' => 'Zone 1', 'code' => 'Z-01', 'type' => 'zone', 'status' => 'active']);
        $rack = Location::query()->create(['parent_id' => $zone->id, 'name' => 'Rack 1', 'code' => 'R-01', 'type' => 'rack', 'status' => 'active']);
        $sourceBin = Location::query()->create(['parent_id' => $rack->id, 'name' => 'Dock Bin', 'code' => 'BIN-SRC', 'type' => 'bin', 'status' => 'active']);
        $destBin = Location::query()->create(['parent_id' => $rack->id, 'name' => 'Storage Bin', 'code' => 'BIN-DST', 'type' => 'bin', 'status' => 'active']);

        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'MOVE-LOT-01',
        ]);

        $balance = StockBalance::query()->create([
            'batch_id' => $batch->id,
            'location_id' => $sourceBin->id,
            'quantity' => '20.000',
        ]);

        BinInventory::query()->create([
            'product_id' => $product->id,
            'location_id' => $sourceBin->id,
            'batch_number' => $batch->batch_number,
            'quantity' => '20.000',
        ]);

        $this->actingAs($user)
            ->post(route('stock-balances.put-away', $balance), [
                'destination_location_id' => $destBin->id,
                'quantity' => '15.000',
                'idempotency_key' => (string) Str::uuid(),
            ])
            ->assertRedirect(route('inventory.index'));

        // Check source balance was reduced
        $this->assertSame('5.000', $balance->fresh()->quantity);

        // Check destination balance was created
        $destBalance = StockBalance::query()
            ->where('batch_id', $batch->id)
            ->where('location_id', $destBin->id)
            ->firstOrFail();
        $this->assertSame('15.000', $destBalance->quantity);

        // Check movement audit record
        $this->assertDatabaseHas('stock_movements', [
            'batch_id' => $batch->id,
            'from_location_id' => $sourceBin->id,
            'to_location_id' => $destBin->id,
            'movement_type' => 'put_away',
            'quantity' => '15.000',
        ]);
    }

    public function test_batch_index_filters_and_detail_view_shows_movements(): void
    {
        $auditor = User::factory()->create(['role' => User::ROLE_AUDITOR]);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => 'Laundry Bar Soap']);

        $batchNearExpiry = Batch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'EXP-SOON-01',
            'manufactured_at' => today()->subMonths(2),
            'expires_at' => today()->addDays(15),
        ]);

        $batchExpired = Batch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'EXP-PAST-01',
            'manufactured_at' => today()->subYear(),
            'expires_at' => today()->subDays(5),
        ]);

        // Access index and filter
        $this->actingAs($auditor)
            ->get(route('batches.index', ['search' => 'EXP-SOON']))
            ->assertOk()
            ->assertSee('EXP-SOON-01')
            ->assertDontSee('EXP-PAST-01');

        $this->actingAs($auditor)
            ->get(route('batches.index', ['expiry_status' => 'expired']))
            ->assertOk()
            ->assertSee('EXP-PAST-01');

        // Access batch details
        $this->actingAs($auditor)
            ->get(route('batches.show', $batchNearExpiry))
            ->assertOk()
            ->assertSee('EXP-SOON-01')
            ->assertSee('Laundry Bar Soap')
            ->assertSee('Stock Movement Ledger');
    }
}
