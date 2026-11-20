<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\BinInventory;
use App\Models\Location;
use App\Models\ManualStockAdjustment;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualStockAdjustmentService
{
    public function __construct(
        private AuditService $auditService,
    ) {}

    /**
     * Create a pending adjustment request.
     * Stock is not changed at this stage.
     */
    public function create(array $attributes, User $requester): ManualStockAdjustment
    {
        return DB::transaction(function () use ($attributes, $requester): ManualStockAdjustment {
            $batch = Batch::query()
                ->lockForUpdate()
                ->findOrFail($attributes['batch_id']);

            if ((int) $batch->product_id !== (int) $attributes['product_id']) {
                throw ValidationException::withMessages([
                    'batch_id' => 'The selected batch does not belong to this product.',
                ]);
            }

            $location = Location::query()
                ->lockForUpdate()
                ->findOrFail($attributes['location_id']);

            if (
                $location->type !== 'bin'
                || ! $location->isOperational()
            ) {
                throw ValidationException::withMessages([
                    'location_id' => 'Choose an active operational bin.',
                ]);
            }

            $balance = StockBalance::query()
                ->where('batch_id', $batch->id)
                ->where('location_id', $location->id)
                ->lockForUpdate()
                ->first();

            $legacyInventory = BinInventory::query()
                ->where('location_id', $location->id)
                ->where('product_id', $batch->product_id)
                ->where('batch_number', $batch->batch_number)
                ->lockForUpdate()
                ->first();

            $balanceUnits = $balance === null
                ? 0
                : $this->quantityUnits($balance->quantity);

            $legacyUnits = $legacyInventory === null
                ? 0
                : $this->quantityUnits($legacyInventory->quantity);

            if ($balanceUnits !== $legacyUnits) {
                throw ValidationException::withMessages([
                    'location_id' => 'The stock balance and bin inventory do not match. Reconcile the stock records before requesting an adjustment.',
                ]);
            }

            $quantityUnits = $this->quantityUnits(
                (string) $attributes['adjustment_quantity']
            );

            if ($quantityUnits < 1) {
                throw ValidationException::withMessages([
                    'adjustment_quantity' => 'Enter an adjustment quantity greater than zero.',
                ]);
            }

            if (
                $attributes['direction'] === 'decrease'
                && $quantityUnits > $balanceUnits
            ) {
                throw ValidationException::withMessages([
                    'adjustment_quantity' => 'The requested decrease cannot exceed the available stock.',
                ]);
            }

            $adjustment = ManualStockAdjustment::query()->create([
                'product_id' => $batch->product_id,
                'batch_id' => $batch->id,
                'location_id' => $location->id,
                'requested_by' => $requester->id,
                'current_quantity' => $this->formatQuantity($balanceUnits),
                'adjustment_quantity' => $this->formatQuantity($quantityUnits),
                'direction' => $attributes['direction'],
                'reason' => trim($attributes['reason']),
                'status' => 'pending',
            ]);

            $this->auditService->record($adjustment, 'created', [], [
                'product_id' => $batch->product_id,
                'batch_id' => $batch->id,
                'location_id' => $location->id,
                'current_quantity' => $adjustment->current_quantity,
                'adjustment_quantity' => $adjustment->adjustment_quantity,
                'direction' => $adjustment->direction,
                'reason' => $adjustment->reason,
                'status' => 'pending',
                'requested_by' => $requester->id,
            ]);

            return $adjustment->load(
                'product',
                'batch',
                'location',
                'requester',
            );
        }, attempts: 3);
    }

    /**
     * Approve or reject a pending adjustment request.
     */
    public function review(
        ManualStockAdjustment $manualStockAdjustment,
        string $decision,
        ?string $reason,
        User $reviewer,
    ): ManualStockAdjustment {
        return DB::transaction(function () use (
            $manualStockAdjustment,
            $decision,
            $reason,
            $reviewer,
        ): ManualStockAdjustment {
            $adjustment = ManualStockAdjustment::query()
                ->lockForUpdate()
                ->findOrFail($manualStockAdjustment->id);

            if ($adjustment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'decision' => 'Only pending adjustment requests can be reviewed.',
                ]);
            }

            if (! in_array($decision, ['approved', 'rejected'], true)) {
                throw ValidationException::withMessages([
                    'decision' => 'Choose approve or reject.',
                ]);
            }

            if ($adjustment->requested_by === $reviewer->id) {
                throw ValidationException::withMessages([
                    'decision' => 'You cannot approve or reject your own adjustment request.',
                ]);
            }

            if (! $reviewer->hasPermission('Approve or reject manual stock adjustments')) {
                abort(403);
            }

            if ($decision === 'approved') {
                $this->applyApprovedAdjustment($adjustment, $reviewer);
            }

            $oldStatus = $adjustment->status;

            $adjustment->forceFill([
                'status' => $decision,
                'reviewed_by' => $reviewer->id,
                'review_note' => $reason === null ? null : trim($reason),
                'reviewed_at' => now(),
            ])->save();

            $this->auditService->record($adjustment, $decision, [
                'status' => $oldStatus,
            ], [
                'status' => $decision,
                'review_note' => $adjustment->review_note,
                'reviewed_by' => $reviewer->id,
            ]);

            return $adjustment->load(
                'product',
                'batch',
                'location',
                'requester',
                'reviewer',
            );
        }, attempts: 3);
    }

    /**
     * Apply the stock change after approval.
     */
    private function applyApprovedAdjustment(
        ManualStockAdjustment $adjustment,
        User $reviewer,
    ): void {
        $batch = Batch::query()
            ->lockForUpdate()
            ->findOrFail($adjustment->batch_id);

        $location = Location::query()
            ->lockForUpdate()
            ->findOrFail($adjustment->location_id);

        if (! $location->isOperational()) {
            throw ValidationException::withMessages([
                'adjustment' => 'The selected bin is no longer operational.',
            ]);
        }

        $balance = StockBalance::query()
            ->where('batch_id', $batch->id)
            ->where('location_id', $location->id)
            ->lockForUpdate()
            ->first();

        $legacyInventory = BinInventory::query()
            ->where('location_id', $location->id)
            ->where('product_id', $batch->product_id)
            ->where('batch_number', $batch->batch_number)
            ->lockForUpdate()
            ->first();

        $currentBalanceUnits = $balance === null
            ? 0
            : $this->quantityUnits($balance->quantity);

        $currentLegacyUnits = $legacyInventory === null
            ? 0
            : $this->quantityUnits($legacyInventory->quantity);

        $snapshotUnits = $this->quantityUnits($adjustment->current_quantity);

        if (
            $currentBalanceUnits !== $snapshotUnits
            || $currentLegacyUnits !== $snapshotUnits
        ) {
            throw ValidationException::withMessages([
                'adjustment' => 'Stock has changed since this request was created. Reject this request and create a new adjustment using the current stock quantity.',
            ]);
        }

        $adjustmentUnits = $this->quantityUnits(
            $adjustment->adjustment_quantity
        );

        $newUnits = $adjustment->direction === 'increase'
            ? $currentBalanceUnits + $adjustmentUnits
            : $currentBalanceUnits - $adjustmentUnits;

        if ($newUnits < 0) {
            throw ValidationException::withMessages([
                'adjustment' => 'The adjustment would result in negative stock.',
            ]);
        }

        if ($newUnits === 0) {
            $balance?->delete();
            $legacyInventory?->delete();
        } else {
            if ($balance === null) {
                StockBalance::query()->create([
                    'batch_id' => $batch->id,
                    'location_id' => $location->id,
                    'quantity' => $this->formatQuantity($newUnits),
                ]);
            } else {
                $balance->quantity = $this->formatQuantity($newUnits);
                $balance->save();
            }

            if ($legacyInventory === null) {
                BinInventory::query()->create([
                    'location_id' => $location->id,
                    'product_id' => $batch->product_id,
                    'batch_number' => $batch->batch_number,
                    'quantity' => $this->formatQuantity($newUnits),
                ]);
            } else {
                $legacyInventory->quantity = $this->formatQuantity($newUnits);
                $legacyInventory->save();
            }
        }

        $movement = StockMovement::query()->create([
            'batch_id' => $batch->id,
            'from_location_id' => $adjustment->direction === 'decrease'
                ? $location->id
                : null,
            'to_location_id' => $adjustment->direction === 'increase'
                ? $location->id
                : null,
            'performed_by' => $adjustment->requested_by,
            'approved_by' => $reviewer->id,
            'movement_type' => 'adjustment',
            'reason' => $adjustment->reason,
            'quantity' => $this->formatQuantity($adjustmentUnits),
            'reference_type' => 'manual_stock_adjustment',
            'reference_id' => $adjustment->id,
            'moved_at' => now(),
        ]);

        $this->auditService->record($movement, 'stock_adjusted', [], [
            'manual_stock_adjustment_id' => $adjustment->id,
            'product_id' => $batch->product_id,
            'batch_id' => $batch->id,
            'location_id' => $location->id,
            'previous_quantity' => $this->formatQuantity($currentBalanceUnits),
            'adjustment_quantity' => $this->formatQuantity($adjustmentUnits),
            'direction' => $adjustment->direction,
            'new_quantity' => $this->formatQuantity($newUnits),
            'reason' => $adjustment->reason,
            'requested_by' => $adjustment->requested_by,
            'approved_by' => $reviewer->id,
        ]);
    }

    private function quantityUnits(string|int|float $quantity): int
    {
        $quantity = number_format((float) $quantity, 3, '.', '');

        [$whole, $fraction] = array_pad(
            explode('.', $quantity, 2),
            2,
            '000',
        );

        return ((int) $whole * 1000) + (int) $fraction;
    }

    private function formatQuantity(int $units): string
    {
        return intdiv($units, 1000).'.'.str_pad(
            (string) ($units % 1000),
            3,
            '0',
            STR_PAD_LEFT,
        );
    }
}
