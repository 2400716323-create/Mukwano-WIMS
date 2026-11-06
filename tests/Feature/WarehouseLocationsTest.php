<?php

namespace Tests\Feature;

use App\Models\BinInventory;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\QrCodeService;
use Database\Seeders\LocationSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WarehouseLocationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_tree_can_be_created_searched_and_edited(): void
    {
        $this->actingAs(User::factory()->create());
        $warehouse = $this->createLocation('warehouse', 'Namanve Warehouse');
        $zone = $this->createLocation('zone', 'Zone A03', ['parent_id' => $warehouse->id]);
        $rack = $this->createLocation('rack', 'Rack 02', ['parent_id' => $zone->id]);

        $this->post(route('locations.store'), [
            'type' => 'bin',
            'parent_id' => $rack->id,
            'name' => 'Bin 02',
            'code' => 'A03-B02',
        ])->assertRedirect();

        $bin = Location::query()->where('code', 'A03-B02')->firstOrFail();
        $this->put(route('locations.update', $bin), [
            'name' => 'Overflow Bin',
            'code' => 'A03-B02',
        ])->assertRedirect(route('locations.show', $bin));

        $this->get(route('locations.index', ['search' => 'A03-B02']))
            ->assertOk()
            ->assertSee('Namanve Warehouse')
            ->assertSee('Overflow Bin');
        $this->assertDatabaseHas('locations', [
            'id' => $bin->id,
            'name' => 'Overflow Bin',
            'code' => 'A03-B02',
        ]);
    }

    public function test_bin_codes_are_required_and_unique(): void
    {
        $this->actingAs(User::factory()->create());
        $warehouse = $this->createLocation('warehouse', 'Namanve Warehouse');
        $zone = $this->createLocation('zone', 'Zone A03', ['parent_id' => $warehouse->id]);
        $rack = $this->createLocation('rack', 'Rack 01', ['parent_id' => $zone->id]);
        $this->createLocation('bin', 'First bin', [
            'parent_id' => $rack->id,
            'code' => 'A03-B02',
        ]);

        $this->post(route('locations.store'), [
            'type' => 'bin',
            'parent_id' => $rack->id,
            'name' => 'Duplicate bin',
            'code' => 'A03-B02',
        ])->assertSessionHasErrors('code');

        $this->post(route('locations.store'), [
            'type' => 'bin',
            'parent_id' => $rack->id,
            'name' => 'Missing code',
        ])->assertSessionHasErrors('code');

        $this->expectException(QueryException::class);
        DB::table('locations')->insert([
            'parent_id' => $rack->id,
            'type' => 'bin',
            'name' => 'Database duplicate',
            'code' => 'A03-B02',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_hierarchy_and_inactive_parent_rules_are_enforced(): void
    {
        $this->actingAs(User::factory()->create());
        $warehouse = $this->createLocation('warehouse', 'Namanve Warehouse');
        $zone = $this->createLocation('zone', 'Zone A03', ['parent_id' => $warehouse->id]);
        $rack = $this->createLocation('rack', 'Rack 01', ['parent_id' => $zone->id]);
        $bin = $this->createLocation('bin', 'Bin 01', [
            'parent_id' => $rack->id,
            'code' => 'A03-B01',
        ]);

        $this->post(route('locations.store'), [
            'type' => 'rack',
            'parent_id' => $warehouse->id,
            'name' => 'Invalid rack parent',
        ])->assertSessionHasErrors('parent_id');

        $this->patch(route('locations.deactivate', $warehouse))->assertRedirect(route('locations.index'));
        $this->post(route('locations.store'), [
            'type' => 'bin',
            'parent_id' => $rack->id,
            'name' => 'Bin under inactive ancestor',
            'code' => 'A03-B02',
        ])->assertSessionHasErrors('parent_id');
        $this->patch(route('locations.activate', $warehouse))->assertRedirect(route('locations.show', $warehouse));

        $this->patch(route('locations.deactivate', $zone))->assertRedirect(route('locations.index'));
        $this->assertFalse($bin->fresh()->isOperational());

        $this->post(route('locations.store'), [
            'type' => 'rack',
            'parent_id' => $zone->id,
            'name' => 'Inactive parent rack',
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_inactive_bins_reject_receipts_and_active_bins_track_occupancy(): void
    {
        $this->actingAs(User::factory()->create());
        $warehouse = $this->createLocation('warehouse', 'Namanve Warehouse');
        $zone = $this->createLocation('zone', 'Zone A03', ['parent_id' => $warehouse->id]);
        $rack = $this->createLocation('rack', 'Rack 01', ['parent_id' => $zone->id]);
        $bin = $this->createLocation('bin', 'Bin 01', [
            'parent_id' => $rack->id,
            'code' => 'A03-B01',
        ]);
        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();

        $this->patch(route('locations.deactivate', $bin))->assertRedirect(route('locations.index'));
        $receiptData = [
            'supplier_id' => $supplier->id,
            'lines' => [[
                'product_id' => $product->id,
                'batch_number' => 'BATCH-1',
                'quantity' => '5.000',
                'location_id' => $bin->id,
            ]],
        ];
        $this->post(route('goods-receipts.review'), $receiptData)
            ->assertSessionHasErrors('lines.0.location_id');
        $this->assertDatabaseCount('bin_inventory', 0);

        $this->patch(route('locations.activate', $bin))->assertRedirect(route('locations.show', $bin));
        $review = $this->post(route('goods-receipts.review'), $receiptData)->assertRedirect();
        preg_match('/goods-receipts\/review\/([0-9a-f-]{36})/', $review->headers->get('Location'), $matches);
        $this->assertArrayHasKey(1, $matches);
        $this->assertDatabaseCount('goods_receipts', 0);
        $this->post(route('goods-receipts.confirm', $matches[1]))->assertRedirect();

        $this->get(route('locations.index'))
            ->assertOk()
            ->assertSee('Occupied')
            ->assertSee('Namanve Warehouse');
        $this->get(route('locations.show', $bin))
            ->assertOk()
            ->assertSee('BATCH-1')
            ->assertSee('5.000');
        $this->assertDatabaseHas('bin_inventory', [
            'location_id' => $bin->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH-1',
            'quantity' => 5,
        ]);
    }

    public function test_receipts_merge_existing_batches_and_reject_invalid_quantities(): void
    {
        $this->actingAs(User::factory()->create());
        $bin = $this->createBin();
        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();
        $receiptData = [
            'supplier_id' => $supplier->id,
            'lines' => [[
                'product_id' => $product->id,
                'batch_number' => 'BATCH-1',
                'quantity' => '2.000',
                'location_id' => $bin->id,
            ]],
        ];

        $firstReview = $this->post(route('goods-receipts.review'), $receiptData);
        preg_match('/goods-receipts\/review\/([0-9a-f-]{36})/', $firstReview->headers->get('Location'), $firstMatches);
        $this->post(route('goods-receipts.confirm', $firstMatches[1]))->assertRedirect();

        $receiptData['lines'][0]['quantity'] = '1.000';
        $secondReview = $this->post(route('goods-receipts.review'), $receiptData);
        preg_match('/goods-receipts\/review\/([0-9a-f-]{36})/', $secondReview->headers->get('Location'), $secondMatches);
        $this->post(route('goods-receipts.confirm', $secondMatches[1]))->assertRedirect();

        $this->assertDatabaseHas('bin_inventory', [
            'location_id' => $bin->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH-1',
            'quantity' => '3.000',
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'location_id' => $bin->id,
            'quantity' => '3.000',
        ]);

        $receiptData['lines'][0]['quantity'] = '0';
        $this->post(route('goods-receipts.review'), $receiptData)
            ->assertSessionHasErrors('lines.0.quantity');
    }

    public function test_batch_move_merges_matching_inventory_at_the_destination(): void
    {
        $this->actingAs(User::factory()->create());
        $source = $this->createBin();
        $destination = $this->createBin('A03-B02');
        $product = Product::factory()->create();
        $sourceInventory = BinInventory::query()->create([
            'location_id' => $source->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH-1',
            'quantity' => 5,
        ]);
        BinInventory::query()->create([
            'location_id' => $destination->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH-1',
            'quantity' => 2,
        ]);

        $this->post(route('bin-inventory.move', $sourceInventory), [
            'destination_location_id' => $destination->id,
        ])->assertRedirect(route('locations.show', $source));

        $this->assertDatabaseMissing('bin_inventory', ['id' => $sourceInventory->id]);
        $this->assertDatabaseHas('bin_inventory', [
            'location_id' => $destination->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH-1',
            'quantity' => 7,
        ]);
    }

    public function test_batch_move_rejects_inactive_destination_ancestors(): void
    {
        $this->actingAs(User::factory()->create());
        $source = $this->createBin();
        $destination = $this->createBin('A03-B02');
        $product = Product::factory()->create();
        $inventory = BinInventory::query()->create([
            'location_id' => $source->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH-1',
            'quantity' => 5,
        ]);
        $destinationWarehouse = $destination->parent->parent->parent;
        $this->patch(route('locations.deactivate', $destinationWarehouse))
            ->assertRedirect(route('locations.index'));

        $this->post(route('bin-inventory.move', $inventory), [
            'destination_location_id' => $destination->id,
        ])->assertSessionHasErrors('destination_location_id');

        $this->assertDatabaseHas('bin_inventory', [
            'id' => $inventory->id,
            'location_id' => $source->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseMissing('bin_inventory', [
            'location_id' => $destination->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH-1',
        ]);
    }

    public function test_qr_labels_generate_svg_and_server_validates_signed_codes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $bin = $this->createBin();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $qrCodeService = app(QrCodeService::class);

        $locationCode = $qrCodeService->codeFor('location', $bin->id);
        $productCode = $qrCodeService->codeFor('product', $product->id);

        $this->get(route('locations.label', $bin))
            ->assertOk()
            ->assertSee('viewBox="0 0 45 45"', false)
            ->assertSee($locationCode);
        $this->get(route('products.label', $product))
            ->assertOk()
            ->assertSee('viewBox="0 0 45 45"', false)
            ->assertSee($productCode);

        $this->postJson(route('qr.resolve'), ['code' => $locationCode])
            ->assertOk()
            ->assertJsonPath('type', 'location')
            ->assertJsonPath('code', $bin->code)
            ->assertJsonPath('path', 'Namanve Warehouse / Zone A03 / Rack 01 / Bin A03-B01');
        $this->postJson(route('qr.resolve'), ['code' => $productCode])
            ->assertOk()
            ->assertJsonPath('type', 'product')
            ->assertJsonPath('sku', $product->sku);

        $tamperedCode = $qrCodeService->codeFor('location', $bin->id + 1);
        $this->postJson(route('qr.resolve'), ['code' => $tamperedCode])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code')
            ->assertDontSee($bin->name);
        $this->postJson(route('qr.resolve'), ['code' => 'W1|L|1|invalid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_qr_scanner_and_location_details_require_authentication(): void
    {
        $bin = $this->createBin();
        $code = app(QrCodeService::class)->codeFor('location', $bin->id);

        $this->get(route('qr.scan'))->assertRedirect(route('login'));
        $this->get(route('locations.label', $bin))->assertRedirect(route('login'));
        $this->postJson(route('qr.resolve'), ['code' => $code])->assertUnauthorized();

        $this->actingAs(User::factory()->create())
            ->get(route('qr.scan'))
            ->assertOk()
            ->assertSee('BarcodeDetector');
    }

    public function test_namanve_location_seeder_is_repeatable(): void
    {
        $this->seed(LocationSeeder::class);
        $this->seed(LocationSeeder::class);

        $this->assertDatabaseHas('locations', [
            'type' => 'warehouse',
            'name' => 'Namanve Warehouse',
        ]);
        $this->assertDatabaseHas('locations', [
            'type' => 'bin',
            'code' => 'A03-B02',
        ]);
        $this->assertSame(1, Location::query()->where('code', 'A03-B02')->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createLocation(string $type, string $name, array $overrides = []): Location
    {
        return Location::query()->create([
            'type' => $type,
            'name' => $name,
            'parent_id' => null,
            'code' => null,
            'status' => 'active',
            ...$overrides,
        ]);
    }

    private function createBin(string $code = 'A03-B01'): Location
    {
        $warehouse = $this->createLocation('warehouse', 'Namanve Warehouse');
        $zone = $this->createLocation('zone', 'Zone A03', ['parent_id' => $warehouse->id]);
        $rack = $this->createLocation('rack', 'Rack 01', ['parent_id' => $zone->id]);

        return $this->createLocation('bin', 'Bin '.$code, [
            'parent_id' => $rack->id,
            'code' => $code,
        ]);
    }
}
