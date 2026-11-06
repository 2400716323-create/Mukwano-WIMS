<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\InventorySetting;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\StockCount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AlertGenerationService
{
    /**
     * @return Collection<int, Alert>
     */
    public function generate(): Collection
    {
        return DB::transaction(function (): Collection {
            $conditions = $this->conditions();
            $activeKeys = [];
            $newlyActiveAlerts = collect();
            $now = now();

            foreach ($conditions as $condition) {
                $key = $condition['condition_key'];
                $activeKeys[$key] = true;
                $alert = Alert::query()->where('condition_key', $key)->lockForUpdate()->first();

                if ($alert === null) {
                    $newlyActiveAlerts->push(Alert::query()->create([
                        ...$condition,
                        'condition_active' => true,
                        'last_seen_at' => $now,
                    ]));

                    continue;
                }

                $wasInactive = ! $alert->condition_active;
                $alert->fill([
                    ...$condition,
                    'condition_active' => true,
                    'last_seen_at' => $now,
                    ...($wasInactive ? ['resolved_at' => null, 'resolved_by' => null] : []),
                ])->save();

                if ($wasInactive) {
                    $newlyActiveAlerts->push($alert);
                }
            }

            Alert::query()->where('condition_active', true)->get()->each(
                function (Alert $alert) use ($activeKeys, $now): void {
                    if (isset($activeKeys[$alert->condition_key])) {
                        return;
                    }

                    $alert->forceFill([
                        'condition_active' => false,
                        'resolved_at' => $alert->resolved_at ?? $now,
                        'resolved_by' => $alert->resolved_at === null ? null : $alert->resolved_by,
                    ])->save();
                },
            );

            return $newlyActiveAlerts;
        });
    }

    /**
     * @return list<array{
     *     condition_key: string,
     *     type: string,
     *     severity: string,
     *     title: string,
     *     description: string,
     *     action_url: string|null
     * }>
     */
    private function conditions(): array
    {
        $conditions = [];
        $stockByProduct = StockBalance::query()
            ->join('batches', 'stock_balances.batch_id', '=', 'batches.id')
            ->selectRaw('batches.product_id, SUM(stock_balances.quantity) as total_quantity')
            ->groupBy('batches.product_id')
            ->pluck('total_quantity', 'product_id');

        foreach (Product::query()->where('status', 'active')->where('reorder_level', '>', 0)->get() as $product) {
            $onHandUnits = $this->quantityUnits((string) ($stockByProduct[$product->id] ?? '0'));
            if ($onHandUnits >= $this->quantityUnits((string) $product->reorder_level)) {
                continue;
            }

            $conditions[] = [
                'condition_key' => "low-stock:product:{$product->id}",
                'type' => 'low_stock',
                'severity' => 'high',
                'title' => "Reorder level breached: {$product->name}",
                'description' => sprintf(
                    '%s has %s %s on hand against a reorder level of %s.',
                    $product->sku,
                    number_format($onHandUnits / 1000, 3, '.', ''),
                    $product->unit,
                    $product->reorder_level,
                ),
                'action_url' => route('inventory.enquiry', ['product_id' => $product->id]),
            ];
        }

        $today = today();
        $expiryWindows = InventorySetting::current()->expiryWindows();
        $stockBalances = StockBalance::query()
            ->where('quantity', '>', 0)
            ->with('batch.product', 'location.parent.parent.parent')
            ->whereHas('batch', fn ($query) => $query->whereNotNull('expires_at')
                ->whereDate('expires_at', '<=', $today->copy()->addDays($expiryWindows['notice'])))
            ->get();

        foreach ($stockBalances as $balance) {
            $location = $balance->location;
            $batch = $balance->batch;
            if ($location->type !== 'bin' || ! $location->isOperational() || $batch->expires_at === null) {
                continue;
            }

            $daysUntilExpiry = (int) $today->diffInDays(Carbon::parse($batch->expires_at)->startOfDay(), false);
            $expiryWindow = match (true) {
                $daysUntilExpiry < 0 => 'expired',
                $daysUntilExpiry <= $expiryWindows['soon'] => "{$expiryWindows['soon']} days",
                $daysUntilExpiry <= $expiryWindows['warning'] => "{$expiryWindows['warning']} days",
                $daysUntilExpiry <= $expiryWindows['notice'] => "{$expiryWindows['notice']} days",
                default => null,
            };
            if ($expiryWindow === null) {
                continue;
            }

            $conditions[] = [
                'condition_key' => "expiry:batch:{$batch->id}:location:{$location->id}",
                'type' => 'expiry',
                'severity' => match ($expiryWindow) {
                    'expired' => 'critical',
                    "{$expiryWindows['soon']} days" => 'high',
                    "{$expiryWindows['warning']} days" => 'medium',
                    default => 'low',
                },
                'title' => $expiryWindow === 'expired'
                    ? "Expired stock: {$batch->product->name}"
                    : "Stock expiring within {$expiryWindow}: {$batch->product->name}",
                'description' => sprintf(
                    'Batch %s at %s has %s %s; expiry date %s.',
                    $batch->batch_number,
                    $location->code ?? $location->name,
                    $balance->quantity,
                    $batch->product->unit,
                    $batch->expires_at->toDateString(),
                ),
                'action_url' => route('inventory.enquiry', [
                    'product_id' => $batch->product_id,
                    'batch_id' => $batch->id,
                    'location_id' => $location->id,
                ]),
            ];
        }

        foreach (PurchaseOrder::query()
            ->whereIn('status', [
                PurchaseOrder::STATUS_DRAFT,
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ])
            ->with('supplier')
            ->get() as $purchaseOrder) {
            $conditions[] = [
                'condition_key' => "pending-purchase-order:{$purchaseOrder->id}",
                'type' => 'pending_purchase_order',
                'severity' => 'medium',
                'title' => "Pending purchase order {$purchaseOrder->order_number}",
                'description' => "{$purchaseOrder->supplier->name} · ".ucwords(str_replace('_', ' ', $purchaseOrder->status)),
                'action_url' => route('purchase-orders.show', $purchaseOrder),
            ];
        }

        foreach (StockCount::query()->where('status', 'submitted')->with('location')->get() as $stockCount) {
            $conditions[] = [
                'condition_key' => "pending-stock-count:{$stockCount->id}",
                'type' => 'pending_stock_count',
                'severity' => 'high',
                'title' => "Cycle count #{$stockCount->id} awaiting approval",
                'description' => "Submitted count awaiting supervisor review at {$stockCount->location->code}.",
                'action_url' => route('stock-counts.show', $stockCount),
            ];
        }

        return $conditions;
    }

    private function quantityUnits(string $quantity): int
    {
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '000');

        return ((int) $whole * 1000) + (int) str_pad(substr($fraction, 0, 3), 3, '0');
    }
}
