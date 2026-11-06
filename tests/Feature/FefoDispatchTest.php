<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BinInventory;
use App\Models\DispatchRequest;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\FefoPickingService;
use App\Services\StockReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class FefoDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_fefo_proposal_orders_expiry_and_excludes_expired_or_inactive_bins(): void
    {
        $product = Product::factory()->create();
        $firstBin = $this->createBin('D01');
        $inactiveBin = $this->createBin('D02');
        $inactiveBin->parent->update(['status' => 'inactive']);
        $earlyBatch = $this->createStock($product, $firstBin, 'EARLY', '2.000', today()->addDays(10)->toDateString());
        $lateBatch = $this->createStock($product, $firstBin, 'LATE', '3.000', today()->addDays(30)->toDateString());
        $this->createStock($product, $firstBin, 'EXPIRED', '9.000', today()->subDay()->toDateString());
        $this->createStock($product, $inactiveBin, 'INACTIVE-BIN', '9.000', today()->addDay()->toDateString());

        $proposal = app(FefoPickingService::class)->propose($product->id, '4.000');

        $this->assertSame([$earlyBatch->id, $lateBatch->id], array_column($proposal['allocations'], 'batch_id'));
        $this->assertSame(['2.000', '2.000'], array_column($proposal['allocations'], 'proposed_quantity'));
        $this->assertTrue($proposal['sufficient']);
        $this->assertCount(2, $proposal['allocations']);
    }

    public function test_fefo_dispatch_allocates_across_batches_posts_ledger_and_generates_printable_gdn(): void
    {
        $requester = User::factory()->create();
        $officer = $this->createDispatchOfficer();
        $this->actingAs($requester);
        $product = Product::factory()->create();
        $firstBin = $this->createBin('D03');
        $secondBin = $this->createBin('D04');
        $firstBatch = $this->createStock($product, $firstBin, 'FIRST', '2.000', today()->addDays(10)->toDateString());
        $secondBatch = $this->createStock($product, $secondBin, 'SECOND', '5.000', today()->addDays(20)->toDateString());
        $dispatchRequest = $this->createRequest($product, '4.500', $requester);
        $proposal = app(FefoPickingService::class)->propose($product->id, '4.500');

        $this->get(route('dispatch-requests.pick-list', $dispatchRequest))
            ->assertOk()
            ->assertSee('FEFO pick list')
            ->assertSee('FIRST')
            ->assertSee('SECOND');

        $this->actingAs($officer)
            ->post(route('dispatch-requests.post', $dispatchRequest), [
                'picks' => $this->proposedPicks($proposal),
            ])
            ->assertRedirect(route('dispatch-requests.print', $dispatchRequest));

        $dispatchRequest->refresh();
        $this->assertSame('dispatched', $dispatchRequest->status);
        $this->assertMatchesRegularExpression('/\AGDN-\d{4}-\d{6}\z/', $dispatchRequest->gdn_number);
        $this->assertSame($officer->id, $dispatchRequest->dispatched_by);
        $this->assertDatabaseMissing('stock_balances', [
            'batch_id' => $firstBatch->id,
            'location_id' => $firstBin->id,
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $secondBatch->id,
            'location_id' => $secondBin->id,
            'quantity' => '2.500',
        ]);
        $this->assertDatabaseCount('dispatch_lines', 2);
        $this->assertDatabaseCount('stock_movements', 4);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => DispatchRequest::class,
            'auditable_id' => $dispatchRequest->id,
            'event' => 'dispatched',
            'user_id' => $officer->id,
        ]);
        $this->assertTrue(app(StockReconciliationService::class)->reconcile()['matched']);
        $this->get(route('dispatch-requests.print', $dispatchRequest))
            ->assertOk()
            ->assertSee($dispatchRequest->gdn_number)
            ->assertSee('Goods Delivery Note');
    }

    public function test_expired_stock_is_not_used_and_over_dispatch_is_rejected(): void
    {
        $officer = $this->createDispatchOfficer();
        $this->actingAs($officer);
        $product = Product::factory()->create();
        $bin = $this->createBin('D05');
        $availableBatch = $this->createStock($product, $bin, 'AVAILABLE', '3.000', today()->addDays(4)->toDateString());
        $this->createStock($product, $bin, 'EXPIRED-ONLY', '10.000', today()->subDay()->toDateString());
        $dispatchRequest = $this->createRequest($product, '4.000', User::factory()->create());
        $proposal = app(FefoPickingService::class)->propose($product->id, '4.000');

        $this->assertFalse($proposal['sufficient']);
        $this->assertSame([$availableBatch->id], array_column($proposal['allocations'], 'batch_id'));

        $this->post(route('dispatch-requests.post', $dispatchRequest), [
            'picks' => [$proposal['allocations'][0]['balance_id'] => '4.000'],
        ])->assertSessionHasErrors('picks.'.$proposal['allocations'][0]['balance_id']);

        $this->assertSame('requested', $dispatchRequest->fresh()->status);
        $this->assertDatabaseCount('dispatch_lines', 0);
        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $availableBatch->id,
            'location_id' => $bin->id,
            'quantity' => '3.000',
        ]);
    }

    public function test_fefo_deviation_requires_a_reason_and_dispatch_officer_can_authorize_it(): void
    {
        $requester = User::factory()->create();
        $officer = $this->createDispatchOfficer();
        $this->actingAs($requester);
        $product = Product::factory()->create();
        $bin = $this->createBin('D06');
        $earlyBatch = $this->createStock($product, $bin, 'EARLY', '3.000', today()->addDays(5)->toDateString());
        $lateBatch = $this->createStock($product, $bin, 'LATE', '5.000', today()->addDays(25)->toDateString());
        $dispatchRequest = $this->createRequest($product, '2.000', $requester);
        $proposal = app(FefoPickingService::class)->propose($product->id, '2.000');
        $deviatedPicks = [
            $proposal['allocations'][0]['balance_id'] => '0.000',
            $proposal['allocations'][1]['balance_id'] => '2.000',
        ];

        $this->actingAs($officer)
            ->post(route('dispatch-requests.post', $dispatchRequest), ['picks' => $deviatedPicks])
            ->assertSessionHasErrors('deviation_reason');
        $this->assertSame('requested', $dispatchRequest->fresh()->status);

        $this->post(route('dispatch-requests.post', $dispatchRequest), [
            'picks' => $deviatedPicks,
            'deviation_reason' => 'Earlier lot is reserved for an urgent order.',
        ])->assertRedirect(route('dispatch-requests.print', $dispatchRequest));

        $dispatchRequest->refresh();
        $this->assertSame('Earlier lot is reserved for an urgent order.', $dispatchRequest->deviation_reason);
        $this->assertDatabaseHas('dispatch_lines', [
            'dispatch_request_id' => $dispatchRequest->id,
            'batch_id' => $lateBatch->id,
            'quantity' => '2.000',
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $earlyBatch->id,
            'quantity' => '3.000',
        ]);
    }

    public function test_using_another_bin_for_the_same_batch_does_not_count_as_fefo_deviation(): void
    {
        $requester = User::factory()->create();
        $officer = $this->createDispatchOfficer();
        $product = Product::factory()->create();
        $firstBin = $this->createBin('D09');
        $secondBin = $this->createBin('D10');
        $batch = $this->createStock($product, $firstBin, 'SAME-BATCH', '2.000', today()->addDays(5)->toDateString());
        $this->addStockForBatch($product, $batch, $secondBin, '2.000');
        $dispatchRequest = $this->createRequest($product, '2.000', $requester);
        $proposal = app(FefoPickingService::class)->propose($product->id, '2.000');

        $this->actingAs($officer)
            ->post(route('dispatch-requests.post', $dispatchRequest), [
                'picks' => [
                    $proposal['allocations'][0]['balance_id'] => '0.000',
                    $proposal['allocations'][1]['balance_id'] => '2.000',
                ],
            ])
            ->assertRedirect(route('dispatch-requests.print', $dispatchRequest));

        $this->assertSame('dispatched', $dispatchRequest->fresh()->status);
        $this->assertNull($dispatchRequest->fresh()->deviation_reason);
        $this->assertDatabaseHas('dispatch_lines', [
            'dispatch_request_id' => $dispatchRequest->id,
            'batch_id' => $batch->id,
            'location_id' => $secondBin->id,
            'quantity' => '2.000',
        ]);
    }

    public function test_dispatch_post_rolls_back_balances_ledger_lines_and_gdn_on_failure(): void
    {
        $requester = User::factory()->create();
        $officer = $this->createDispatchOfficer();
        $product = Product::factory()->create();
        $bin = $this->createBin('D07');
        $batch = $this->createStock($product, $bin, 'ROLLBACK', '5.000', today()->addDays(5)->toDateString());
        $balance = StockBalance::query()->where('batch_id', $batch->id)->firstOrFail();
        $dispatchRequest = $this->createRequest($product, '2.000', $requester);
        $proposal = app(FefoPickingService::class)->propose($product->id, '2.000');
        StockMovement::creating(function (StockMovement $movement): void {
            if ($movement->movement_type === 'dispatch') {
                throw new RuntimeException('Simulated dispatch ledger failure.');
            }
        });

        try {
            app(FefoPickingService::class)->post(
                $dispatchRequest,
                $this->proposedPicks($proposal),
                null,
                $officer,
            );
            $this->fail('A ledger failure must abort dispatch posting.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated dispatch ledger failure.', $exception->getMessage());
        }

        $this->assertSame('requested', $dispatchRequest->fresh()->status);
        $this->assertNull($dispatchRequest->fresh()->gdn_number);
        $this->assertDatabaseHas('stock_balances', [
            'id' => $balance->id,
            'quantity' => '5.000',
        ]);
        $this->assertDatabaseHas('bin_inventory', [
            'batch_number' => $batch->batch_number,
            'quantity' => '5.000',
        ]);
        $this->assertDatabaseCount('dispatch_lines', 0);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_only_dispatch_officers_can_post_and_stock_is_revalidated_for_each_request(): void
    {
        $requester = User::factory()->create();
        $this->actingAs($requester);
        $product = Product::factory()->create();
        $bin = $this->createBin('D08');
        $batch = $this->createStock($product, $bin, 'LIMITED', '5.000', today()->addDays(5)->toDateString());
        $firstRequest = $this->createRequest($product, '4.000', $requester);
        $secondRequest = $this->createRequest($product, '4.000', $requester);
        $proposal = app(FefoPickingService::class)->propose($product->id, '4.000');

        $this->post(route('dispatch-requests.post', $firstRequest), [
            'picks' => $this->proposedPicks($proposal),
        ])->assertForbidden();

        $officer = $this->createDispatchOfficer();
        $this->actingAs($officer)
            ->post(route('dispatch-requests.post', $firstRequest), [
                'picks' => $this->proposedPicks($proposal),
            ])->assertRedirect();
        $freshProposal = app(FefoPickingService::class)->propose($product->id, '4.000');
        $this->assertFalse($freshProposal['sufficient']);
        $this->post(route('dispatch-requests.post', $secondRequest), [
            'picks' => $this->proposedPicks($proposal),
        ])->assertSessionHasErrors('picks.'.$proposal['allocations'][0]['balance_id']);

        $this->assertSame('dispatched', $firstRequest->fresh()->status);
        $this->assertSame('requested', $secondRequest->fresh()->status);
        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'quantity' => '1.000',
        ]);
    }

    public function test_authenticated_users_can_create_dispatch_requests_and_view_request_page(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->actingAs($user);
        $this->get(route('dispatch-requests.create'))
            ->assertOk()
            ->assertSee('New dispatch request');

        $this->post(route('dispatch-requests.store'), [
            'product_id' => $product->id,
            'quantity' => '1.250',
        ])->assertRedirect();

        $this->get(route('dispatch-requests.index'))
            ->assertOk()
            ->assertSee('DR-1')
            ->assertSee($product->sku);
    }

    public function test_dispatch_officer_role_can_be_granted_and_removed_from_the_console(): void
    {
        $user = User::factory()->create();

        $this->artisan('users:set-role', ['email' => $user->email, 'role' => 'dispatch_officer'])
            ->assertExitCode(0);
        $this->assertTrue($user->fresh()->isDispatchOfficer());

        $this->artisan('users:set-role', ['email' => $user->email, 'role' => 'user'])
            ->assertExitCode(0);
        $this->assertFalse($user->fresh()->isDispatchOfficer());
    }

    /**
     * @param  array{allocations: array<int, array{balance_id: int, proposed_quantity: string}>}  $proposal
     * @return array<int, string>
     */
    private function proposedPicks(array $proposal): array
    {
        $picks = [];
        foreach ($proposal['allocations'] as $allocation) {
            $picks[$allocation['balance_id']] = $allocation['proposed_quantity'];
        }

        return $picks;
    }

    private function createRequest(Product $product, string $quantity, User $requester): DispatchRequest
    {
        return DispatchRequest::query()->create([
            'product_id' => $product->id,
            'requested_quantity' => $quantity,
            'requested_by' => $requester->id,
            'requested_at' => now(),
        ]);
    }

    private function createDispatchOfficer(): User
    {
        $officer = User::factory()->create();
        $officer->forceFill(['role' => 'dispatch_officer'])->save();

        return $officer;
    }

    private function createStock(
        Product $product,
        Location $bin,
        string $batchNumber,
        string $quantity,
        string $expiresAt,
    ): Batch {
        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'batch_number' => $batchNumber.'-'.Str::random(5),
            'expires_at' => $expiresAt,
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

    private function addStockForBatch(Product $product, Batch $batch, Location $bin, string $quantity): void
    {
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
    }

    private function createBin(string $code): Location
    {
        $warehouse = Location::query()->create([
            'type' => 'warehouse',
            'name' => 'Warehouse '.$code,
            'status' => 'active',
        ]);
        $zone = Location::query()->create([
            'parent_id' => $warehouse->id,
            'type' => 'zone',
            'name' => 'Zone '.$code,
            'status' => 'active',
        ]);
        $rack = Location::query()->create([
            'parent_id' => $zone->id,
            'type' => 'rack',
            'name' => 'Rack '.$code,
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
