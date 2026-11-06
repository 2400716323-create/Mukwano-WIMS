<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BinInventory;
use App\Models\InventorySetting;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockCountReview;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockCountService;
use App\Services\StockReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CycleCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_variance_calculation_accuracy_formula_and_tolerance(): void
    {
        $counter = User::factory()->create();
        $product = Product::factory()->create();
        $bin = $this->createBin();
        $firstBatch = $this->createStock($product, $bin, 'COUNT-A', '100.000');
        $secondBatch = $this->createStock($product, $bin, 'COUNT-B', '50.000');
        $count = $this->createCount($product, $bin, null, $counter);
        InventorySetting::query()->create([
            'accuracy_tolerance_percent' => '2.000',
            'variance_threshold_percent' => '5.000',
        ]);
        $lines = $count->lines()->orderBy('batch_id')->get();

        $this->actingAs($counter)->post(route('stock-counts.submit', $count), [
            'lines' => [
                $lines[0]->id => ['physical_quantity' => '99.000', 'discrepancy_reason' => 'One unit misplaced'],
                $lines[1]->id => ['physical_quantity' => '45.000', 'discrepancy_reason' => 'Damaged units removed'],
            ],
        ])->assertRedirect(route('stock-counts.show', $count));

        $this->assertSame('-1.000', $lines[0]->fresh()->variance);
        $this->assertFalse($lines[0]->fresh()->exceeds_variance_threshold);
        $this->assertSame('-5.000', $lines[1]->fresh()->variance);
        $this->assertTrue($lines[1]->fresh()->exceeds_variance_threshold);

        $this->get(route('stock-counts.show', $count))
            ->assertOk()
            ->assertSee('Counted records')
            ->assertSee('Matching records')
            ->assertSee('Discrepancies')
            ->assertSee('Accuracy = matching records / counted records');
        $accuracy = app(StockCountService::class)->accuracy($count);
        $this->assertSame([
            'counted' => 2,
            'matching' => 1,
            'discrepancies' => 1,
            'accuracy_percent' => 50.0,
        ], $accuracy);
        $this->assertSame(2, $firstBatch->fresh()->stockBalances()->count() + $secondBatch->fresh()->stockBalances()->count());
    }

    public function test_stock_does_not_change_until_supervisor_approval_and_posts_audited_adjustments(): void
    {
        $counter = User::factory()->create();
        $supervisor = $this->createSupervisor();
        $product = Product::factory()->create();
        $bin = $this->createBin();
        $batch = $this->createStock($product, $bin, 'APPROVAL', '10.000');
        $count = $this->createCount($product, $bin, $batch, $counter);
        $line = $count->lines()->firstOrFail();

        $this->actingAs($counter)->post(route('stock-counts.submit', $count), [
            'lines' => [
                $line->id => [
                    'physical_quantity' => '7.500',
                    'discrepancy_reason' => 'Two units broken and half a unit spilled.',
                ],
            ],
        ])->assertRedirect();

        $this->assertSame('10.000', $this->balance($batch)->quantity);
        $this->assertDatabaseHas('bin_inventory', [
            'batch_number' => $batch->batch_number,
            'quantity' => '10.000',
        ]);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('stock_count_reviews', 0);

        $this->actingAs($supervisor)->post(route('stock-counts.review', $count), [
            'decision' => 'approved',
            'reason' => 'Verified the physical recount.',
        ])->assertRedirect();

        $this->assertSame('7.500', $this->balance($batch)->quantity);
        $this->assertDatabaseHas('bin_inventory', [
            'batch_number' => $batch->batch_number,
            'quantity' => '7.500',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'stock_count_line_id' => $line->id,
            'movement_type' => 'adjustment',
            'from_location_id' => $bin->id,
            'to_location_id' => null,
            'quantity' => '2.500',
            'performed_by' => $counter->id,
            'approved_by' => $supervisor->id,
            'reason' => 'Two units broken and half a unit spilled.',
        ]);
        $this->assertTrue(app(StockReconciliationService::class)->reconcile()['matched']);
        $this->assertDatabaseHas('stock_count_reviews', [
            'stock_count_id' => $count->id,
            'supervisor_id' => $supervisor->id,
            'decision' => 'approved',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => StockCount::class,
            'auditable_id' => $count->id,
            'event' => 'submitted',
            'user_id' => $counter->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => StockCount::class,
            'auditable_id' => $count->id,
            'event' => 'approved',
            'user_id' => $supervisor->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => StockMovement::class,
            'event' => 'stock_adjusted',
            'user_id' => $supervisor->id,
        ]);
        $this->get(route('inventory.ledger', ['movement_type' => 'adjustment']))
            ->assertOk()
            ->assertSee('Stock movement ledger')
            ->assertSee('Two units broken and half a unit spilled.')
            ->assertSee($counter->name)
            ->assertSee($supervisor->name);
    }

    public function test_rejected_count_is_audited_and_never_changes_stock(): void
    {
        $counter = User::factory()->create();
        $supervisor = $this->createSupervisor();
        $product = Product::factory()->create();
        $bin = $this->createBin();
        $batch = $this->createStock($product, $bin, 'REJECTED', '5.000');
        $count = $this->createCount($product, $bin, $batch, $counter);
        $line = $count->lines()->firstOrFail();

        $this->actingAs($counter)->post(route('stock-counts.submit', $count), [
            'lines' => [
                $line->id => ['physical_quantity' => '6.000', 'discrepancy_reason' => 'Unexpected stock found'],
            ],
        ])->assertRedirect();

        $this->actingAs($supervisor)->post(route('stock-counts.review', $count), [
            'decision' => 'rejected',
            'reason' => 'Please recount with a witness.',
        ])->assertRedirect();

        $this->assertSame('rejected', $count->fresh()->status);
        $this->assertSame('5.000', $this->balance($batch)->quantity);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame('rejected', StockCountReview::query()->firstOrFail()->decision);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => StockCount::class,
            'auditable_id' => $count->id,
            'event' => 'rejected',
            'user_id' => $supervisor->id,
        ]);
    }

    public function test_approval_adds_newly_discovered_batch_quantity_and_records_incoming_adjustment(): void
    {
        $counter = User::factory()->create();
        $supervisor = $this->createSupervisor();
        $product = Product::factory()->create();
        $bin = $this->createBin();
        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'FOUND-DURING-COUNT',
        ]);
        $count = $this->createCount($product, $bin, $batch, $counter);
        $line = $count->lines()->firstOrFail();

        $this->actingAs($counter)->post(route('stock-counts.submit', $count), [
            'lines' => [
                $line->id => ['physical_quantity' => '2.500', 'discrepancy_reason' => 'Unrecorded batch located in bin'],
            ],
        ])->assertRedirect();
        $this->assertDatabaseMissing('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $bin->id,
        ]);

        $this->actingAs($supervisor)->post(route('stock-counts.review', $count), [
            'decision' => 'approved',
        ])->assertRedirect();

        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $bin->id,
            'quantity' => '2.500',
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'stock_count_line_id' => $line->id,
            'movement_type' => 'adjustment',
            'from_location_id' => null,
            'to_location_id' => $bin->id,
            'quantity' => '2.500',
            'approved_by' => $supervisor->id,
        ]);
    }

    public function test_every_discrepancy_needs_a_reason_and_supervisors_can_configure_settings(): void
    {
        $counter = User::factory()->create();
        $supervisor = $this->createSupervisor();
        $product = Product::factory()->create();
        $bin = $this->createBin();
        $batch = $this->createStock($product, $bin, 'REASON', '5.000');
        $count = $this->createCount($product, $bin, $batch, $counter);
        $line = $count->lines()->firstOrFail();

        $this->actingAs($counter)->post(route('stock-counts.submit', $count), [
            'lines' => [$line->id => ['physical_quantity' => '4.000']],
        ])->assertSessionHasErrors("lines.{$line->id}.discrepancy_reason");
        $this->assertSame('draft', $count->fresh()->status);

        $this->get(route('stock-counts.settings'))->assertForbidden();
        $this->put(route('stock-counts.settings.update'), [
            'variance_threshold_percent' => '8.500',
            'accuracy_tolerance_percent' => '1.250',
        ])->assertForbidden();

        $this->actingAs($supervisor)
            ->get(route('stock-counts.settings'))
            ->assertOk()
            ->assertSee('Variance escalation threshold');
        $this->put(route('stock-counts.settings.update'), [
            'variance_threshold_percent' => '8.500',
            'accuracy_tolerance_percent' => '1.250',
        ])->assertRedirect(route('stock-counts.settings'));

        $this->assertSame('8.500', InventorySetting::current()->variance_threshold_percent);
        $this->assertSame('1.250', InventorySetting::current()->accuracy_tolerance_percent);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => InventorySetting::class,
            'event' => 'updated',
            'user_id' => $supervisor->id,
        ]);
    }

    public function test_only_supervisors_can_review_and_stale_snapshots_cannot_overwrite_new_stock(): void
    {
        $counter = User::factory()->create();
        $supervisor = $this->createSupervisor();
        $product = Product::factory()->create();
        $bin = $this->createBin();
        $batch = $this->createStock($product, $bin, 'STALE', '10.000');
        $count = $this->createCount($product, $bin, $batch, $counter);
        $line = $count->lines()->firstOrFail();

        $this->actingAs($counter)->post(route('stock-counts.submit', $count), [
            'lines' => [$line->id => ['physical_quantity' => '12.000', 'discrepancy_reason' => 'Count variance']],
        ])->assertRedirect();
        $this->post(route('stock-counts.review', $count), ['decision' => 'approved'])->assertForbidden();

        $balance = $this->balance($batch);
        $balance->quantity = '11.000';
        $balance->save();
        BinInventory::query()->where('batch_number', $batch->batch_number)->update(['quantity' => '11.000']);

        $this->actingAs($supervisor)
            ->post(route('stock-counts.review', $count), ['decision' => 'approved'])
            ->assertSessionHasErrors('count');

        $this->assertSame('submitted', $count->fresh()->status);
        $this->assertSame('11.000', $this->balance($batch)->quantity);
        $this->assertDatabaseCount('stock_count_reviews', 0);
    }

    public function test_blind_count_hides_system_quantities_until_submitted(): void
    {
        $counter = User::factory()->create();
        $product = Product::factory()->create();
        $bin = $this->createBin();
        $batch = $this->createStock($product, $bin, 'BLIND', '42.000');
        $count = $this->createCount($product, $bin, $batch, $counter, true);

        $this->actingAs($counter)->get(route('stock-counts.show', $count))
            ->assertOk()
            ->assertSee('Hidden')
            ->assertDontSee('42.000');
    }

    private function createCount(
        Product $product,
        Location $bin,
        ?Batch $batch,
        User $creator,
        bool $blindCount = false,
    ): StockCount {
        return app(StockCountService::class)->create(
            $bin,
            $product->id,
            $batch?->id,
            $blindCount,
            $creator,
        );
    }

    private function createSupervisor(): User
    {
        $supervisor = User::factory()->create();
        $supervisor->forceFill(['role' => 'supervisor'])->save();

        return $supervisor;
    }

    private function createStock(Product $product, Location $bin, string $batchNumber, string $quantity): Batch
    {
        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'batch_number' => $batchNumber,
        ]);
        StockBalance::query()->create([
            'batch_id' => $batch->id,
            'location_id' => $bin->id,
            'quantity' => $quantity,
        ]);
        BinInventory::query()->create([
            'location_id' => $bin->id,
            'product_id' => $product->id,
            'batch_number' => $batch->batch_number,
            'quantity' => $quantity,
        ]);
        StockMovement::query()->create([
            'batch_id' => $batch->id,
            'to_location_id' => $bin->id,
            'movement_type' => 'opening_balance',
            'quantity' => $quantity,
            'reference_type' => 'test',
            'moved_at' => now(),
        ]);

        return $batch;
    }

    private function balance(Batch $batch): StockBalance
    {
        return StockBalance::query()->where('batch_id', $batch->id)->firstOrFail();
    }

    private function createBin(): Location
    {
        $warehouse = Location::query()->create([
            'type' => 'warehouse',
            'name' => 'Cycle Count Warehouse',
            'status' => 'active',
        ]);
        $zone = Location::query()->create([
            'parent_id' => $warehouse->id,
            'type' => 'zone',
            'name' => 'Cycle Count Zone',
            'status' => 'active',
        ]);
        $rack = Location::query()->create([
            'parent_id' => $zone->id,
            'type' => 'rack',
            'name' => 'Cycle Count Rack',
            'status' => 'active',
        ]);

        return Location::query()->create([
            'parent_id' => $rack->id,
            'type' => 'bin',
            'name' => 'Cycle Count Bin',
            'code' => 'CC-'.uniqid(),
            'status' => 'active',
        ]);
    }

    public function test_stock_counts_index_renders_successfully(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $bin = $this->createBin();
        $this->createStock($product, $bin, 'COUNT-1', '10.000');
        $this->createCount($product, $bin, null, $user);

        $this->actingAs($user)
            ->get(route('stock-counts.index'))
            ->assertOk();
    }
}
