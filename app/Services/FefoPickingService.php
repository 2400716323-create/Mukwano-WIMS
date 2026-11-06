<?php

namespace App\Services;

use App\Models\BinInventory;
use App\Models\DispatchLine;
use App\Models\DispatchRequest;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FefoPickingService
{
    public function __construct(private AuditService $auditService) {}

    /**
     * @return array{
     *     allocations: array<int, array{
     *         balance_id: int,
     *         batch_id: int,
     *         batch_number: string,
     *         expires_at: ?string,
     *         location_id: int,
     *         location_code: ?string,
     *         available_quantity: string,
     *         proposed_quantity: string
     *     }>,
     *     requested_quantity: string,
     *     allocated_quantity: string,
     *     sufficient: bool
     * }
     */
    public function propose(int $productId, string $quantity): array
    {
        $requestedUnits = $this->quantityUnits($quantity);
        $remainingUnits = $requestedUnits;
        $allocations = [];

        foreach ($this->availableBalances($productId) as $balance) {
            $availableUnits = $this->quantityUnits($balance->quantity);
            $pickedUnits = min($availableUnits, $remainingUnits);

            $allocations[] = [
                'balance_id' => $balance->id,
                'batch_id' => $balance->batch_id,
                'batch_number' => $balance->batch->batch_number,
                'expires_at' => $balance->batch->expires_at?->toDateString(),
                'location_id' => $balance->location_id,
                'location_code' => $balance->location->code,
                'available_quantity' => $this->formatQuantity($availableUnits),
                'proposed_quantity' => $this->formatQuantity($pickedUnits),
            ];
            $remainingUnits -= $pickedUnits;
        }

        return [
            'allocations' => $allocations,
            'requested_quantity' => $this->formatQuantity($requestedUnits),
            'allocated_quantity' => $this->formatQuantity($requestedUnits - $remainingUnits),
            'sufficient' => $remainingUnits === 0,
        ];
    }

    /**
     * @param  array<int|string, string|int|float>  $pickedQuantities
     */
    public function post(
        DispatchRequest $dispatchRequest,
        array $pickedQuantities,
        ?string $deviationReason,
        User $officer,
    ): DispatchRequest {
        return DB::transaction(function () use (
            $dispatchRequest,
            $pickedQuantities,
            $deviationReason,
            $officer,
        ): DispatchRequest {
            $lockedRequest = DispatchRequest::query()
                ->lockForUpdate()
                ->findOrFail($dispatchRequest->id);

            if ($lockedRequest->status !== 'requested') {
                throw ValidationException::withMessages([
                    'dispatch' => 'This dispatch request has already been posted.',
                ]);
            }
            abort_unless($officer->can('dispatch', $lockedRequest), 403);

            $balances = $this->availableBalances($lockedRequest->product_id, lockForUpdate: true);
            $balancesById = $balances->keyBy('id');
            $pickedUnitsByBalance = [];

            foreach ($pickedQuantities as $balanceId => $quantity) {
                if (! ctype_digit((string) $balanceId) || ! $balancesById->has((int) $balanceId)) {
                    throw ValidationException::withMessages([
                        'picks' => 'The pick list contains a batch or bin that is no longer eligible.',
                    ]);
                }

                $pickedUnits = $this->quantityUnits((string) $quantity);
                $availableUnits = $this->quantityUnits($balancesById->get((int) $balanceId)->quantity);
                if ($pickedUnits > $availableUnits) {
                    throw ValidationException::withMessages([
                        'picks.'.(string) $balanceId => 'The picked quantity exceeds the available stock.',
                    ]);
                }
                $pickedUnitsByBalance[(int) $balanceId] = $pickedUnits;
            }

            $requestedUnits = $this->quantityUnits($lockedRequest->requested_quantity);
            $totalPickedUnits = array_sum($pickedUnitsByBalance);
            if ($totalPickedUnits !== $requestedUnits) {
                throw ValidationException::withMessages([
                    'picks' => 'The confirmed pick quantities must equal the dispatch request quantity.',
                ]);
            }

            $availableUnitsByBatch = [];
            $pickedUnitsByBatch = [];
            foreach ($balances as $balance) {
                $batchId = (int) $balance->batch_id;
                $availableUnitsByBatch[$batchId] = ($availableUnitsByBatch[$batchId] ?? 0)
                    + $this->quantityUnits($balance->quantity);
                $pickedUnitsByBatch[$batchId] = ($pickedUnitsByBatch[$batchId] ?? 0)
                    + ($pickedUnitsByBalance[(int) $balance->id] ?? 0);
            }

            $remainingUnits = $requestedUnits;
            $deviatesFromFefo = false;
            foreach ($availableUnitsByBatch as $batchId => $availableBatchUnits) {
                $expectedBatchUnits = min($availableBatchUnits, $remainingUnits);
                if (($pickedUnitsByBatch[$batchId] ?? 0) !== $expectedBatchUnits) {
                    $deviatesFromFefo = true;
                }
                $remainingUnits -= $expectedBatchUnits;
            }

            if ($remainingUnits > 0) {
                throw ValidationException::withMessages([
                    'picks' => 'Available eligible stock is no longer sufficient to fulfill this dispatch.',
                ]);
            }

            if ($deviatesFromFefo && trim((string) $deviationReason) === '') {
                throw ValidationException::withMessages([
                    'deviation_reason' => 'An authorized reason is required when picks differ from the FEFO pick list.',
                ]);
            }

            $now = now();
            $gdnNumber = 'GDN-'.$now->format('Y').'-'.str_pad((string) $lockedRequest->id, 6, '0', STR_PAD_LEFT);
            $lockedRequest->forceFill([
                'status' => 'dispatched',
                'dispatched_by' => $officer->id,
                'gdn_number' => $gdnNumber,
                'deviation_reason' => $deviatesFromFefo ? trim((string) $deviationReason) : null,
                'dispatched_at' => $now,
            ])->save();

            foreach ($balances as $balance) {
                $pickedUnits = $pickedUnitsByBalance[(int) $balance->id] ?? 0;
                if ($pickedUnits === 0) {
                    continue;
                }

                $this->decrementStock($lockedRequest, $balance, $pickedUnits, $gdnNumber, $officer, $now);
            }

            $lockedRequest->load('product', 'requester', 'dispatcher', 'lines.batch', 'lines.location');
            $this->auditService->record($lockedRequest, 'dispatched', ['status' => 'requested'], [
                'status' => 'dispatched',
                'gdn_number' => $gdnNumber,
                'dispatched_by' => $officer->id,
                'deviation_reason' => $lockedRequest->deviation_reason,
                'lines' => $lockedRequest->lines->map(fn (DispatchLine $line): array => [
                    'batch_id' => $line->batch_id,
                    'location_id' => $line->location_id,
                    'quantity' => $line->quantity,
                ])->all(),
            ]);

            return $lockedRequest;
        }, attempts: 3);
    }

    /**
     * @return Collection<int, StockBalance>
     */
    private function availableBalances(int $productId, bool $lockForUpdate = false): Collection
    {
        $query = StockBalance::query()
            ->select('stock_balances.*')
            ->join('batches', 'stock_balances.batch_id', '=', 'batches.id')
            ->join('locations', 'stock_balances.location_id', '=', 'locations.id')
            ->where('batches.product_id', $productId)
            ->where('stock_balances.quantity', '>', 0)
            ->where('locations.type', 'bin')
            ->where('locations.status', 'active')
            ->where(function ($query): void {
                $query->whereNull('batches.expires_at')
                    ->orWhereDate('batches.expires_at', '>=', today());
            })
            ->with('batch', 'location.parent.parent.parent')
            ->orderByRaw('CASE WHEN batches.expires_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('batches.expires_at')
            ->orderBy('batches.id')
            ->orderBy('locations.code');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get()->filter(function (StockBalance $balance) use ($lockForUpdate): bool {
            return $lockForUpdate
                ? $this->lockOperationalBin($balance->location)
                : $balance->location->isOperational();
        })->values();
    }

    private function lockOperationalBin(Location $bin): bool
    {
        $locationId = $bin->id;
        $expectedType = 'bin';
        $visited = [];

        while ($locationId !== null) {
            if (isset($visited[$locationId])) {
                return false;
            }
            $visited[$locationId] = true;
            $location = Location::query()->lockForUpdate()->findOrFail($locationId);

            if ($location->type !== $expectedType || $location->status !== 'active') {
                return false;
            }
            if ($location->parent_id === null) {
                return $location->type === 'warehouse';
            }

            $expectedType = match ($location->type) {
                'bin' => 'rack',
                'rack' => 'zone',
                'zone' => 'warehouse',
                default => '',
            };
            $locationId = $location->parent_id;
        }

        return false;
    }

    private function decrementStock(
        DispatchRequest $dispatchRequest,
        StockBalance $balance,
        int $pickedUnits,
        string $gdnNumber,
        User $officer,
        Carbon $dispatchedAt,
    ): void {
        $availableUnits = $this->quantityUnits($balance->quantity);
        if ($pickedUnits <= 0 || $pickedUnits > $availableUnits) {
            throw ValidationException::withMessages([
                'picks' => 'The picked quantity exceeds the available stock.',
            ]);
        }

        $legacyInventory = BinInventory::query()
            ->where('location_id', $balance->location_id)
            ->where('product_id', $dispatchRequest->product_id)
            ->where('batch_number', $balance->batch->batch_number)
            ->lockForUpdate()
            ->first();
        if ($legacyInventory === null || $this->quantityUnits($legacyInventory->quantity) < $pickedUnits) {
            throw ValidationException::withMessages([
                'picks' => 'The stock balance does not match the bin inventory. Reconcile stock before dispatch.',
            ]);
        }

        $remainingBalance = $availableUnits - $pickedUnits;
        if ($remainingBalance === 0) {
            $balance->delete();
        } else {
            $balance->quantity = $this->formatQuantity($remainingBalance);
            $balance->save();
        }

        $remainingLegacyUnits = $this->quantityUnits($legacyInventory->quantity) - $pickedUnits;
        if ($remainingLegacyUnits === 0) {
            $legacyInventory->delete();
        } else {
            $legacyInventory->quantity = $this->formatQuantity($remainingLegacyUnits);
            $legacyInventory->save();
        }

        DispatchLine::query()->create([
            'dispatch_request_id' => $dispatchRequest->id,
            'batch_id' => $balance->batch_id,
            'location_id' => $balance->location_id,
            'quantity' => $this->formatQuantity($pickedUnits),
        ]);
        StockMovement::query()->create([
            'batch_id' => $balance->batch_id,
            'from_location_id' => $balance->location_id,
            'performed_by' => $officer->id,
            'movement_type' => 'dispatch',
            'reason' => $dispatchRequest->deviation_reason ?? 'FEFO dispatch '.$gdnNumber,
            'quantity' => $this->formatQuantity($pickedUnits),
            'reference_type' => 'dispatch_request',
            'reference_id' => $dispatchRequest->id,
            'moved_at' => $dispatchedAt,
        ]);
    }

    private function quantityUnits(string $quantity): int
    {
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '000');

        return ((int) $whole * 1000) + (int) str_pad(substr($fraction, 0, 3), 3, '0');
    }

    private function formatQuantity(int $units): string
    {
        return intdiv($units, 1000).'.'.str_pad((string) ($units % 1000), 3, '0', STR_PAD_LEFT);
    }
}
