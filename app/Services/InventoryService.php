<?php

namespace App\Services;

use App\Exceptions\DuplicateGoodsReceiptException;
use App\Models\Batch;
use App\Models\BinInventory;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(private AuditService $auditService) {}

    /**
     * @param  array{
     *     purchase_order_id?: int|null,
     *     supplier_id?: int|null,
     *     notes?: string|null,
     *     accept_discrepancies?: bool,
     *     lines: array<int, array{
     *         product_id: int,
     *         quantity: string,
     *         batch_number: string,
     *         manufactured_at?: string|null,
     *         expires_at?: string|null,
     *         location_id: int
     *     }>
     * }  $receiptData
     */
    public function receive(array $receiptData, User $receiver, string $submissionToken): GoodsReceipt
    {
        try {
            return DB::transaction(function () use ($receiptData, $receiver, $submissionToken): GoodsReceipt {
                if (GoodsReceipt::query()->where('submission_token', $submissionToken)->exists()) {
                    throw new DuplicateGoodsReceiptException;
                }

                $purchaseOrder = null;
                $previousPurchaseOrderStatus = null;
                if (! empty($receiptData['purchase_order_id'])) {
                    $purchaseOrder = PurchaseOrder::query()
                        ->lockForUpdate()
                        ->findOrFail($receiptData['purchase_order_id']);
                    $previousPurchaseOrderStatus = $purchaseOrder->status;

                    if (! in_array($purchaseOrder->status, [
                        PurchaseOrder::STATUS_APPROVED,
                        PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                    ], true)) {
                        throw ValidationException::withMessages([
                            'purchase_order_id' => 'Only approved purchase orders with outstanding quantities can be received.',
                        ]);
                    }
                }

                $receipt = GoodsReceipt::query()->create([
                    'submission_token' => $submissionToken,
                    'purchase_order_id' => $purchaseOrder?->id,
                    'supplier_id' => $purchaseOrder?->supplier_id ?? ($receiptData['supplier_id'] ?? null),
                    'received_by' => $receiver->id,
                    'status' => 'confirmed',
                    'notes' => $receiptData['notes'] ?? null,
                    'received_at' => now(),
                    'confirmed_at' => now(),
                ]);
                $receipt->update([
                    'receipt_number' => 'GRN-'.now()->format('Y').'-'.str_pad((string) $receipt->id, 6, '0', STR_PAD_LEFT),
                ]);

                $lockedPurchaseLines = $purchaseOrder?->lines()
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('product_id');

                foreach ($receiptData['lines'] as $index => $lineData) {
                    $product = Product::query()->lockForUpdate()->findOrFail($lineData['product_id']);
                    $location = Location::query()->lockForUpdate()->findOrFail($lineData['location_id']);
                    $this->ensureOperationalBin($location, $index);

                    $purchaseLine = $lockedPurchaseLines?->get($product->id);
                    if ($purchaseOrder !== null && $purchaseLine === null) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.product_id" => 'The product is not listed on this purchase order.',
                        ]);
                    }

                    if ($purchaseLine !== null) {
                        $outstanding = $this->quantityUnits($purchaseLine->ordered_quantity)
                            - $this->quantityUnits($purchaseLine->received_quantity);
                        $quantity = $this->quantityUnits($lineData['quantity']);

                        if ($quantity > $outstanding && empty($receiptData['accept_discrepancies'])) {
                            throw ValidationException::withMessages([
                                "lines.{$index}.quantity" => 'This receipt exceeds the purchase order outstanding quantity. Acknowledge the discrepancy to continue.',
                            ]);
                        }
                    }

                    $batch = Batch::query()
                        ->where('product_id', $product->id)
                        ->where('batch_number', $lineData['batch_number'])
                        ->lockForUpdate()
                        ->first();

                    if ($batch === null) {
                        $batch = Batch::query()->create([
                            'product_id' => $product->id,
                            'batch_number' => $lineData['batch_number'],
                            'manufactured_at' => $lineData['manufactured_at'] ?? null,
                            'expires_at' => $lineData['expires_at'] ?? null,
                        ]);
                    } else {
                        $this->assertBatchDatesMatch($batch, $lineData, $index);
                    }

                    $receiptLine = GoodsReceiptLine::query()->create([
                        'goods_receipt_id' => $receipt->id,
                        'product_id' => $product->id,
                        'batch_id' => $batch->id,
                        'location_id' => $location->id,
                        'quantity' => $lineData['quantity'],
                    ]);

                    $this->addToBalance($batch, $location, $lineData['quantity']);
                    $this->addToLegacyBinInventory($product, $location, $batch, $lineData['quantity']);

                    StockMovement::query()->create([
                        'batch_id' => $batch->id,
                        'to_location_id' => $location->id,
                        'goods_receipt_line_id' => $receiptLine->id,
                        'performed_by' => $receiver->id,
                        'movement_type' => 'receipt',
                        'quantity' => $lineData['quantity'],
                        'reference_type' => 'goods_receipt',
                        'reference_id' => $receipt->id,
                        'moved_at' => $receipt->confirmed_at,
                    ]);

                    if ($purchaseLine !== null) {
                        $purchaseLine->received_quantity = $this->formatQuantity(
                            $this->quantityUnits($purchaseLine->received_quantity)
                                + $this->quantityUnits($lineData['quantity']),
                        );
                        $purchaseLine->save();
                    }
                }

                if ($purchaseOrder !== null) {
                    $this->refreshPurchaseOrderStatus($purchaseOrder);
                    $purchaseOrder->refresh();
                    $this->auditService->record(
                        $purchaseOrder,
                        'receipt_quantities_updated',
                        ['status' => $previousPurchaseOrderStatus],
                        [
                            'status' => $purchaseOrder->status,
                            'goods_receipt_id' => $receipt->id,
                        ],
                    );
                }

                $this->auditService->record($receipt, 'confirmed', [], [
                    'receipt_number' => $receipt->receipt_number,
                    'purchase_order_id' => $receipt->purchase_order_id,
                    'supplier_id' => $receipt->supplier_id,
                    'received_by' => $receiver->id,
                    'line_count' => count($receiptData['lines']),
                ]);

                return $receipt->load('lines.product', 'lines.batch', 'lines.location', 'supplier', 'purchaseOrder');
            }, attempts: 3);
        } catch (QueryException $exception) {
            if (GoodsReceipt::query()->where('submission_token', $submissionToken)->exists()) {
                throw new DuplicateGoodsReceiptException;
            }

            throw $exception;
        }
    }

    public function putAway(
        StockBalance $balance,
        Location $destination,
        string $quantity,
        User $actor,
        ?string $idempotencyKey = null,
    ): StockMovement {
        return $this->moveBalance(
            $balance,
            $destination,
            $quantity,
            $actor,
            'put_away',
            'Put-away',
            now(),
            $idempotencyKey,
        );
    }

    public function transfer(
        StockBalance $balance,
        Location $destination,
        string $quantity,
        string $reason,
        User $actor,
        Carbon $transferredAt,
        string $idempotencyKey,
    ): StockMovement {
        return $this->moveBalance(
            $balance,
            $destination,
            $quantity,
            $actor,
            'transfer',
            $reason,
            $transferredAt,
            $idempotencyKey,
        );
    }

    private function moveBalance(
        StockBalance $balance,
        Location $destination,
        string $quantity,
        User $actor,
        string $movementType,
        string $reason,
        Carbon $movedAt,
        ?string $idempotencyKey,
    ): StockMovement {
        return DB::transaction(function () use (
            $balance,
            $destination,
            $quantity,
            $actor,
            $movementType,
            $reason,
            $movedAt,
            $idempotencyKey,
        ): StockMovement {
            if (
                $idempotencyKey !== null
                && StockMovement::query()->where('idempotency_key', $idempotencyKey)->exists()
            ) {
                throw ValidationException::withMessages([
                    'destination_location_id' => 'This put-away request has already been processed.',
                ]);
            }

            $lockedBalance = StockBalance::query()
                ->with('batch')
                ->lockForUpdate()
                ->findOrFail($balance->id);
            $lockedBatch = Batch::query()->lockForUpdate()->findOrFail($lockedBalance->batch_id);
            $source = Location::query()->lockForUpdate()->findOrFail($lockedBalance->location_id);
            $target = Location::query()->lockForUpdate()->findOrFail($destination->id);
            if ($source->type !== 'bin') {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock can only be moved from a bin.',
                ]);
            }
            $this->ensureOperationalBin($source);
            abort_unless($actor->can('update', $target), 403);
            $this->ensureOperationalBin($target);

            if ($source->id === $target->id) {
                throw ValidationException::withMessages([
                    'destination_location_id' => 'Choose a different destination bin.',
                ]);
            }

            $movedUnits = $this->quantityUnits($quantity);
            $sourceUnits = $this->quantityUnits($lockedBalance->quantity);
            if ($movedUnits < 1 || $movedUnits > $sourceUnits) {
                throw ValidationException::withMessages([
                    'quantity' => 'The put-away quantity must be positive and no greater than the available balance.',
                ]);
            }

            $targetBalance = StockBalance::query()
                ->where('batch_id', $lockedBatch->id)
                ->where('location_id', $target->id)
                ->lockForUpdate()
                ->first();

            if ($targetBalance === null) {
                $targetBalance = StockBalance::query()->create([
                    'batch_id' => $lockedBatch->id,
                    'location_id' => $target->id,
                    'quantity' => $this->formatQuantity($movedUnits),
                ]);
            } else {
                $targetBalance->quantity = $this->formatQuantity(
                    $this->quantityUnits($targetBalance->quantity) + $movedUnits,
                );
                $targetBalance->save();
            }

            $remainingUnits = $sourceUnits - $movedUnits;
            if ($remainingUnits === 0) {
                $lockedBalance->delete();
            } else {
                $lockedBalance->quantity = $this->formatQuantity($remainingUnits);
                $lockedBalance->save();
            }

            $this->moveLegacyBinInventory(
                $lockedBatch->product_id,
                $lockedBatch->batch_number,
                $source,
                $target,
                $movedUnits,
            );

            $movement = StockMovement::query()->create([
                'batch_id' => $lockedBatch->id,
                'from_location_id' => $source->id,
                'to_location_id' => $target->id,
                'performed_by' => $actor->id,
                'movement_type' => $movementType,
                'reason' => $reason,
                'quantity' => $this->formatQuantity($movedUnits),
                'reference_type' => $movementType,
                'reference_id' => $lockedBalance->id,
                'idempotency_key' => $idempotencyKey,
                'moved_at' => $movedAt,
            ]);
            $this->auditService->record($movement, $movementType === 'transfer' ? 'stock_transferred' : 'stock_put_away', [], [
                'batch_id' => $lockedBatch->id,
                'from_location_id' => $source->id,
                'to_location_id' => $target->id,
                'quantity' => $movement->quantity,
                'reason' => $reason,
                'performed_by' => $actor->id,
            ]);

            return $movement;
        }, attempts: 3);
    }

    public function balanceForLegacyInventory(BinInventory $inventory, User $actor): StockBalance
    {
        return DB::transaction(function () use ($inventory, $actor): StockBalance {
            $legacyInventory = BinInventory::query()->lockForUpdate()->findOrFail($inventory->id);
            $product = Product::query()->lockForUpdate()->findOrFail($legacyInventory->product_id);
            $location = Location::query()->lockForUpdate()->findOrFail($legacyInventory->location_id);
            $batch = Batch::query()
                ->where('product_id', $product->id)
                ->where('batch_number', $legacyInventory->batch_number)
                ->lockForUpdate()
                ->first();

            if ($batch === null) {
                $batch = Batch::query()->create([
                    'product_id' => $product->id,
                    'batch_number' => $legacyInventory->batch_number,
                ]);
            }

            $balance = StockBalance::query()
                ->where('batch_id', $batch->id)
                ->where('location_id', $location->id)
                ->lockForUpdate()
                ->first();

            if ($balance !== null) {
                return $balance;
            }

            $balance = StockBalance::query()->create([
                'batch_id' => $batch->id,
                'location_id' => $location->id,
                'quantity' => $legacyInventory->quantity,
            ]);
            StockMovement::query()->create([
                'batch_id' => $batch->id,
                'to_location_id' => $location->id,
                'performed_by' => $actor->id,
                'movement_type' => 'opening_balance',
                'quantity' => $legacyInventory->quantity,
                'reference_type' => 'bin_inventory',
                'reference_id' => $legacyInventory->id,
                'moved_at' => $legacyInventory->created_at,
            ]);

            return $balance;
        }, attempts: 3);
    }

    private function refreshPurchaseOrderStatus(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->load('lines');
        $receivedUnits = $purchaseOrder->lines->sum(
            fn (PurchaseOrderLine $line): int => $this->quantityUnits($line->received_quantity),
        );
        $outstandingUnits = $purchaseOrder->lines->sum(
            fn (PurchaseOrderLine $line): int => max(
                0,
                $this->quantityUnits($line->ordered_quantity) - $this->quantityUnits($line->received_quantity),
            ),
        );
        $status = $outstandingUnits === 0
            ? PurchaseOrder::STATUS_RECEIVED
            : ($receivedUnits > 0 ? PurchaseOrder::STATUS_PARTIALLY_RECEIVED : PurchaseOrder::STATUS_APPROVED);

        $purchaseOrder->update(['status' => $status]);
    }

    /**
     * @param  array{manufactured_at?: string|null, expires_at?: string|null}  $lineData
     */
    private function assertBatchDatesMatch(Batch $batch, array $lineData, int $index): void
    {
        foreach (['manufactured_at', 'expires_at'] as $dateField) {
            $existingDate = $batch->{$dateField}?->toDateString();
            $receivedDate = $lineData[$dateField] ?? null;

            if ($existingDate !== null && $receivedDate !== null && $existingDate !== $receivedDate) {
                throw ValidationException::withMessages([
                    "lines.{$index}.{$dateField}" => 'The dates do not match the existing product batch.',
                ]);
            }

            if ($existingDate === null && $receivedDate !== null) {
                $batch->update([$dateField => $receivedDate]);
            }
        }
    }

    private function addToBalance(Batch $batch, Location $location, string $quantity): void
    {
        $balance = StockBalance::query()
            ->where('batch_id', $batch->id)
            ->where('location_id', $location->id)
            ->lockForUpdate()
            ->first();

        if ($balance === null) {
            StockBalance::query()->create([
                'batch_id' => $batch->id,
                'location_id' => $location->id,
                'quantity' => $quantity,
            ]);

            return;
        }

        $balance->quantity = $this->formatQuantity(
            $this->quantityUnits($balance->quantity) + $this->quantityUnits($quantity),
        );
        $balance->save();
    }

    private function addToLegacyBinInventory(
        Product $product,
        Location $location,
        Batch $batch,
        string $quantity,
    ): void {
        $inventory = BinInventory::query()
            ->where('location_id', $location->id)
            ->where('product_id', $product->id)
            ->where('batch_number', $batch->batch_number)
            ->lockForUpdate()
            ->first();

        if ($inventory === null) {
            BinInventory::query()->create([
                'location_id' => $location->id,
                'product_id' => $product->id,
                'batch_number' => $batch->batch_number,
                'quantity' => $quantity,
            ]);

            return;
        }

        $inventory->quantity = $this->formatQuantity(
            $this->quantityUnits($inventory->quantity) + $this->quantityUnits($quantity),
        );
        $inventory->save();
    }

    private function moveLegacyBinInventory(
        int $productId,
        string $batchNumber,
        Location $source,
        Location $target,
        int $movedUnits,
    ): void {
        $inventory = BinInventory::query()
            ->where('location_id', $source->id)
            ->where('product_id', $productId)
            ->where('batch_number', $batchNumber)
            ->lockForUpdate()
            ->firstOrFail();
        $remainingUnits = $this->quantityUnits($inventory->quantity) - $movedUnits;

        if ($remainingUnits < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'The legacy bin balance does not match the stock balance.',
            ]);
        }

        if ($remainingUnits === 0) {
            $inventory->delete();
        } else {
            $inventory->quantity = $this->formatQuantity($remainingUnits);
            $inventory->save();
        }

        $targetInventory = BinInventory::query()
            ->where('location_id', $target->id)
            ->where('product_id', $productId)
            ->where('batch_number', $batchNumber)
            ->lockForUpdate()
            ->first();

        if ($targetInventory === null) {
            BinInventory::query()->create([
                'location_id' => $target->id,
                'product_id' => $productId,
                'batch_number' => $batchNumber,
                'quantity' => $this->formatQuantity($movedUnits),
            ]);
        } else {
            $targetInventory->quantity = $this->formatQuantity(
                $this->quantityUnits($targetInventory->quantity) + $movedUnits,
            );
            $targetInventory->save();
        }
    }

    private function ensureOperationalBin(Location $location, ?int $lineIndex = null): void
    {
        $field = $lineIndex === null ? 'destination_location_id' : "lines.{$lineIndex}.location_id";
        $expectedType = 'bin';
        $visited = [];
        $current = Location::query()->lockForUpdate()->findOrFail($location->id);

        while (true) {
            if (isset($visited[$current->id]) || $current->type !== $expectedType || $current->status !== 'active') {
                throw ValidationException::withMessages([
                    $field => 'Stock must be assigned to a bin under an active warehouse hierarchy.',
                ]);
            }
            $visited[$current->id] = true;

            if ($current->parent_id === null) {
                if ($current->type !== 'warehouse') {
                    throw ValidationException::withMessages([
                        $field => 'Stock must be assigned to a bin under an active warehouse hierarchy.',
                    ]);
                }

                return;
            }

            $expectedType = match ($current->type) {
                'bin' => 'rack',
                'rack' => 'zone',
                'zone' => 'warehouse',
                default => '',
            };
            $current = Location::query()->lockForUpdate()->findOrFail($current->parent_id);
        }
    }

    private function quantityUnits(string|int|float $quantity): int
    {
        [$whole, $fraction] = array_pad(explode('.', number_format((float) $quantity, 3, '.', '')), 2, '000');

        return ((int) $whole * 1000) + (int) $fraction;
    }

    private function formatQuantity(int $units): string
    {
        return intdiv($units, 1000).'.'.str_pad((string) ($units % 1000), 3, '0', STR_PAD_LEFT);
    }
}
