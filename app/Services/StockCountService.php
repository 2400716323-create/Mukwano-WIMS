<?php

namespace App\Services;

use App\Models\BinInventory;
use App\Models\InventorySetting;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockCountReview;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockCountService
{
    public function __construct(private AuditService $auditService) {}

    public function create(
        Location $location,
        ?int $productId,
        ?int $batchId,
        bool $blindCount,
        User $creator,
    ): StockCount {
        return DB::transaction(function () use ($location, $productId, $batchId, $blindCount, $creator): StockCount {
            $count = StockCount::query()->create([
                'location_id' => $location->id,
                'product_id' => $productId,
                'batch_id' => $batchId,
                'blind_count' => $blindCount,
                'created_by' => $creator->id,
            ]);

            $balances = StockBalance::query()
                ->with('batch')
                ->where('location_id', $location->id)
                ->when($batchId !== null, fn ($query) => $query->where('batch_id', $batchId))
                ->when(
                    $batchId === null && $productId !== null,
                    fn ($query) => $query->whereHas('batch', fn ($batch) => $batch->where('product_id', $productId)),
                )
                ->orderBy('batch_id')
                ->get();

            if ($balances->isEmpty() && $batchId !== null) {
                $balances = collect([
                    new StockBalance([
                        'batch_id' => $batchId,
                        'location_id' => $location->id,
                        'quantity' => '0.000',
                    ]),
                ]);
            }

            if ($balances->isEmpty()) {
                throw ValidationException::withMessages([
                    'product_id' => 'No stock records match this count. Select a product and batch to count a new or empty batch.',
                ]);
            }

            foreach ($balances as $balance) {
                $count->lines()->create([
                    'batch_id' => $balance->batch_id,
                    'location_id' => $location->id,
                    'system_quantity' => $balance->exists ? $balance->quantity : '0.000',
                ]);
            }

            $this->auditService->record($count, 'created', [], [
                'location_id' => $location->id,
                'product_id' => $productId,
                'batch_id' => $batchId,
                'blind_count' => $blindCount,
                'line_count' => $count->lines()->count(),
            ]);

            return $count->load('lines.batch.product', 'location');
        }, attempts: 3);
    }

    /**
     * Save partially entered quantities without submitting or adjusting stock.
     *
     * @param array<int|string, array{physical_quantity?: string|int|float|null, discrepancy_reason?: string|null}> $draftLines
     */
    public function saveDraft(
        StockCount $stockCount,
        array $draftLines,
        User $counter,
    ): StockCount {
        return DB::transaction(function () use ($stockCount, $draftLines, $counter): StockCount {
            $count = StockCount::query()
                ->lockForUpdate()
                ->findOrFail($stockCount->id);

            if ($count->status !== 'draft') {
                throw ValidationException::withMessages([
                    'count' => 'Only draft count lists can be edited.',
                ]);
            }

            $lines = $count->lines()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $settings = InventorySetting::current();
            $oldValues = [];
            $newValues = [];

            foreach ($draftLines as $lineId => $input) {
                $line = $lines->get((int) $lineId);

                if ($line === null) {
                    throw ValidationException::withMessages([
                        'lines' => 'The submitted count list contains an invalid line.',
                    ]);
                }

                $updates = [];

                // Keep an existing physical quantity if the field is blank.
                $physicalInput = $input['physical_quantity'] ?? null;

                if ($physicalInput !== null && $physicalInput !== '') {
                    $physicalUnits = $this->quantityUnits((string) $physicalInput);
                    $systemUnits = $this->quantityUnits($line->system_quantity);
                    $varianceUnits = $physicalUnits - $systemUnits;

                    $variancePercent = $this->variancePercent(
                        $systemUnits,
                        $varianceUnits,
                    );

                    $updates['physical_quantity'] = $this->formatQuantity($physicalUnits);
                    $updates['variance'] = $this->formatSignedQuantity($varianceUnits);
                    $updates['counted_by'] = $counter->id;
                    $updates['exceeds_variance_threshold'] =
                        abs($variancePercent) > (float) $settings->variance_threshold_percent;
                }

                // Save a reason if the reason field was included in the form.
                if (array_key_exists('discrepancy_reason', $input)) {
                    $reason = trim((string) ($input['discrepancy_reason'] ?? ''));

                    $updates['discrepancy_reason'] = $reason === '' ? null : $reason;
                }

                if ($updates === []) {
                    continue;
                }

                $oldValues[$line->id] = [
                    'physical_quantity' => $line->physical_quantity,
                    'variance' => $line->variance,
                    'discrepancy_reason' => $line->discrepancy_reason,
                ];

                $line->forceFill($updates)->save();

                $newValues[$line->id] = [
                    'physical_quantity' => $line->physical_quantity,
                    'variance' => $line->variance,
                    'discrepancy_reason' => $line->discrepancy_reason,
                ];
            }

            if ($newValues !== []) {
                $this->auditService->record($count, 'draft_saved', $oldValues, [
                    'lines' => $newValues,
                    'status' => 'draft',
                ]);
            }

            return $count->load('lines.batch.product', 'location');
        }, attempts: 3);
    }

    /**
     * @param array<int|string, array{physical_quantity: string|int|float, discrepancy_reason?: string|null}> $countedLines
     */
    public function submit(
        StockCount $stockCount,
        array $countedLines,
        User $counter,
    ): StockCount {
        return DB::transaction(function () use ($stockCount, $countedLines, $counter): StockCount {
            $count = StockCount::query()->lockForUpdate()->findOrFail($stockCount->id);

            if ($count->status !== 'draft') {
                throw ValidationException::withMessages([
                    'count' => 'Only draft count lists can be submitted.',
                ]);
            }

            $lines = $count->lines()->lockForUpdate()->get()->keyBy('id');

            if ($lines->isEmpty() || count($countedLines) !== $lines->count()) {
                throw ValidationException::withMessages([
                    'lines' => 'Enter a physical quantity for every count line.',
                ]);
            }

            $settings = InventorySetting::current();
            $counted = 0;
            $discrepancies = 0;

            foreach ($countedLines as $lineId => $input) {
                $line = $lines->get((int) $lineId);

                if ($line === null) {
                    throw ValidationException::withMessages([
                        'lines' => 'The submitted count list contains an invalid line.',
                    ]);
                }

                $physicalUnits = $this->quantityUnits((string) $input['physical_quantity']);
                $systemUnits = $this->quantityUnits($line->system_quantity);
                $varianceUnits = $physicalUnits - $systemUnits;

                $reason = trim((string) ($input['discrepancy_reason'] ?? ''));

                if ($varianceUnits !== 0 && $reason === '') {
                    throw ValidationException::withMessages([
                        "lines.{$lineId}.discrepancy_reason" => 'Provide a reason for this stock discrepancy.',
                    ]);
                }

                $variancePercent = $this->variancePercent($systemUnits, $varianceUnits);
                $exceedsThreshold = abs($variancePercent) > (float) $settings->variance_threshold_percent;

                $line->forceFill([
                    'physical_quantity' => $this->formatQuantity($physicalUnits),
                    'variance' => $this->formatSignedQuantity($varianceUnits),
                    'discrepancy_reason' => $reason === '' ? null : $reason,
                    'counted_by' => $counter->id,
                    'exceeds_variance_threshold' => $exceedsThreshold,
                ])->save();

                $counted++;

                if ($varianceUnits !== 0) {
                    $discrepancies++;
                }
            }

            $count->forceFill([
                'status' => 'submitted',
                'submitted_by' => $counter->id,
                'submitted_at' => now(),
            ])->save();

            $this->auditService->record($count, 'submitted', ['status' => 'draft'], [
                'status' => 'submitted',
                'counted_records' => $counted,
                'discrepancies' => $discrepancies,
                'variance_threshold_percent' => $settings->variance_threshold_percent,
            ]);

            return $count->load('lines.batch.product', 'location', 'submitter');
        }, attempts: 3);
    }

    public function review(
        StockCount $stockCount,
        string $decision,
        ?string $reason,
        User $supervisor,
    ): StockCount {
        return DB::transaction(function () use ($stockCount, $decision, $reason, $supervisor): StockCount {
            $count = StockCount::query()->lockForUpdate()->findOrFail($stockCount->id);

            if ($count->status !== 'submitted') {
                throw ValidationException::withMessages([
                    'count' => 'Only submitted count lists can be approved or rejected.',
                ]);
            }

            abort_unless($supervisor->isSupervisor(), 403);

            if ($decision === 'approved') {
                $this->applyApprovedCount($count, $supervisor);
            }

            $now = now();

            $count->forceFill([
                'status' => $decision,
                'reviewed_by' => $supervisor->id,
                'review_note' => $reason,
                'reviewed_at' => $now,
            ])->save();

            StockCountReview::query()->create([
                'stock_count_id' => $count->id,
                'supervisor_id' => $supervisor->id,
                'decision' => $decision,
                'reason' => $reason,
                'reviewed_at' => $now,
            ]);

            $this->auditService->record($count, $decision, ['status' => 'submitted'], [
                'status' => $decision,
                'review_note' => $reason,
                'supervisor_id' => $supervisor->id,
            ]);

            return $count->load(
                'lines.batch.product',
                'location',
                'submitter',
                'reviewer',
                'reviews.supervisor',
            );
        }, attempts: 3);
    }

    /**
     * @return array{counted: int, matching: int, discrepancies: int, accuracy_percent: float}
     */
    public function accuracy(StockCount $stockCount): array
    {
        return $this->calculateAccuracy(
            $stockCount->lines()->whereNotNull('physical_quantity')->get(),
            InventorySetting::current(),
        );
    }

    /**
     * @param Collection<int, StockCountLine> $lines
     * @return array{counted: int, matching: int, discrepancies: int, accuracy_percent: float}
     */
    public function accuracyUsingLoadedLines(
        Collection $lines,
        InventorySetting $settings,
    ): array {
        return $this->calculateAccuracy(
            $lines->whereNotNull('physical_quantity'),
            $settings,
        );
    }

    /**
     * @param Collection<int, StockCountLine> $lines
     * @return array{counted: int, matching: int, discrepancies: int, accuracy_percent: float}
     */
    private function calculateAccuracy(
        Collection $lines,
        InventorySetting $settings,
    ): array {
        $counted = $lines->count();

        $matching = $lines->filter(function (StockCountLine $line) use ($settings): bool {
            $systemUnits = $this->quantityUnits($line->system_quantity);
            $varianceUnits = $this->quantityUnits((string) $line->variance);

            return abs($this->variancePercent($systemUnits, $varianceUnits))
                <= (float) $settings->accuracy_tolerance_percent;
        })->count();

        return [
            'counted' => $counted,
            'matching' => $matching,
            'discrepancies' => $counted - $matching,
            'accuracy_percent' => $counted === 0
                ? 0.0
                : round(($matching / $counted) * 100, 2),
        ];
    }

    private function applyApprovedCount(StockCount $count, User $supervisor): void
    {
        $lines = $count->lines()->lockForUpdate()->orderBy('id')->get();

        if (
            $lines->isEmpty()
            || $lines->contains(fn (StockCountLine $line): bool => $line->physical_quantity === null)
        ) {
            throw ValidationException::withMessages([
                'count' => 'Every count line must have a physical quantity before approval.',
            ]);
        }

        $adjustments = [];

        foreach ($lines as $line) {
            $balance = StockBalance::query()
                ->where('batch_id', $line->batch_id)
                ->where('location_id', $line->location_id)
                ->lockForUpdate()
                ->first();

            $batch = $line->batch()->lockForUpdate()->firstOrFail();

            Location::query()->lockForUpdate()->findOrFail($line->location_id);

            $legacyInventory = BinInventory::query()
                ->where('location_id', $line->location_id)
                ->where('product_id', $batch->product_id)
                ->where('batch_number', $batch->batch_number)
                ->lockForUpdate()
                ->first();

            $systemUnits = $this->quantityUnits($line->system_quantity);

            $currentBalanceUnits = $balance === null
                ? 0
                : $this->quantityUnits($balance->quantity);

            $currentLegacyUnits = $legacyInventory === null
                ? 0
                : $this->quantityUnits($legacyInventory->quantity);

            if ($currentBalanceUnits !== $systemUnits || $currentLegacyUnits !== $systemUnits) {
                throw ValidationException::withMessages([
                    'count' => "Stock in batch {$batch->batch_number} changed since the count was created. Create a new count before approval.",
                ]);
            }

            $physicalUnits = $this->quantityUnits((string) $line->physical_quantity);
            $varianceUnits = $physicalUnits - $systemUnits;

            if ($varianceUnits !== 0) {
                $adjustments[] = [
                    $line,
                    $batch,
                    $balance,
                    $legacyInventory,
                    $physicalUnits,
                    $varianceUnits,
                ];
            }
        }

        foreach ($adjustments as [$line, $batch, $balance, $legacyInventory, $physicalUnits, $varianceUnits]) {
            if ($physicalUnits === 0) {
                $balance?->delete();
                $legacyInventory?->delete();
            } elseif ($balance === null) {
                StockBalance::query()->create([
                    'batch_id' => $line->batch_id,
                    'location_id' => $line->location_id,
                    'quantity' => $this->formatQuantity($physicalUnits),
                ]);

                BinInventory::query()->create([
                    'location_id' => $line->location_id,
                    'product_id' => $batch->product_id,
                    'batch_number' => $batch->batch_number,
                    'quantity' => $this->formatQuantity($physicalUnits),
                ]);
            } else {
                $balance->quantity = $this->formatQuantity($physicalUnits);
                $balance->save();

                $legacyInventory->quantity = $this->formatQuantity($physicalUnits);
                $legacyInventory->save();
            }

            $countedBy = $line->counted_by ?? $count->submitted_by;

            $movement = StockMovement::query()->create([
                'batch_id' => $line->batch_id,
                'from_location_id' => $varianceUnits < 0 ? $line->location_id : null,
                'to_location_id' => $varianceUnits > 0 ? $line->location_id : null,
                'stock_count_line_id' => $line->id,
                'performed_by' => $countedBy,
                'approved_by' => $supervisor->id,
                'movement_type' => 'adjustment',
                'reason' => $line->discrepancy_reason,
                'quantity' => $this->formatQuantity(abs($varianceUnits)),
                'reference_type' => 'stock_count',
                'reference_id' => $count->id,
                'moved_at' => now(),
            ]);

            $this->auditService->record($movement, 'stock_adjusted', [], [
                'stock_count_id' => $count->id,
                'stock_count_line_id' => $line->id,
                'batch_id' => $line->batch_id,
                'location_id' => $line->location_id,
                'system_quantity' => $line->system_quantity,
                'physical_quantity' => $line->physical_quantity,
                'variance' => $line->variance,
                'reason' => $line->discrepancy_reason,
                'approved_by' => $supervisor->id,
            ]);
        }
    }

    private function variancePercent(int $systemUnits, int $varianceUnits): float
    {
        if ($systemUnits === 0) {
            return $varianceUnits === 0 ? 0.0 : 100.0;
        }

        return ($varianceUnits / $systemUnits) * 100;
    }

    private function quantityUnits(string $quantity): int
    {
        $negative = str_starts_with($quantity, '-');
        $quantity = ltrim($quantity, '+-');

        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '000');

        $units = ((int) $whole * 1000)
            + (int) str_pad(substr($fraction, 0, 3), 3, '0');

        return $negative ? -$units : $units;
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

    private function formatSignedQuantity(int $units): string
    {
        return $units < 0
            ? '-'.$this->formatQuantity(abs($units))
            : $this->formatQuantity($units);
    }
}
