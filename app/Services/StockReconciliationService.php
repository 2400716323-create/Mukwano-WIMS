<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StockReconciliationService
{
    /**
     * @return array{
     *     matched: bool,
     *     checked: int,
     *     mismatches: array<int, array{batch_id: int, location_id: int, balance: string, ledger: string}>
     * }
     */
    public function reconcile(): array
    {
        $details = $this->details();
        $mismatches = array_values(array_map(
            static fn (array $row): array => [
                'batch_id' => $row['batch_id'],
                'location_id' => $row['location_id'],
                'balance' => $row['balance'],
                'ledger' => $row['ledger'],
            ],
            array_filter($details, static fn (array $row): bool => ! $row['matched']),
        ));

        return [
            'matched' => $mismatches === [],
            'checked' => count($details),
            'mismatches' => $mismatches,
        ];
    }

    /**
     * @return list<array{
     *     product_id: int,
     *     sap_material_number: string|null,
     *     sku: string,
     *     product_name: string,
     *     batch_id: int,
     *     batch_number: string,
     *     location_id: int,
     *     location_code: string,
     *     balance: string,
     *     ledger: string,
     *     variance: string,
     *     matched: bool
     * }>
     */
    public function details(): array
    {
        $balanceRows = DB::table('stock_balances')
            ->select('batch_id', 'location_id', 'quantity')
            ->selectRaw("'balance' as row_type");
        $receivedRows = DB::table('stock_movements')
            ->whereNotNull('to_location_id')
            ->select('batch_id')
            ->selectRaw('to_location_id as location_id')
            ->addSelect('quantity')
            ->selectRaw("'received' as row_type");
        $issuedRows = DB::table('stock_movements')
            ->whereNotNull('from_location_id')
            ->select('batch_id')
            ->selectRaw('from_location_id as location_id')
            ->addSelect('quantity')
            ->selectRaw("'issued' as row_type");
        $rows = DB::query()
            ->fromSub($balanceRows->unionAll($receivedRows)->unionAll($issuedRows), 'stock_rows')
            ->get();
        $totals = [];

        foreach ($rows as $row) {
            $key = $row->batch_id.'|'.$row->location_id;
            $totals[$key] ??= [
                'batch_id' => (int) $row->batch_id,
                'location_id' => (int) $row->location_id,
                'balance' => 0,
                'ledger' => 0,
            ];
            $quantityUnits = $this->quantityUnits((string) $row->quantity);

            match ($row->row_type) {
                'balance' => $totals[$key]['balance'] += $quantityUnits,
                'received' => $totals[$key]['ledger'] += $quantityUnits,
                'issued' => $totals[$key]['ledger'] -= $quantityUnits,
            };
        }

        $batchIds = array_values(array_unique(array_column($totals, 'batch_id')));
        $locationIds = array_values(array_unique(array_column($totals, 'location_id')));
        $batches = DB::table('batches')
            ->join('products', 'batches.product_id', '=', 'products.id')
            ->whereIn('batches.id', $batchIds)
            ->get([
                'batches.id',
                'batches.product_id',
                'batches.batch_number',
                'products.sap_material_number',
                'products.sku',
                'products.name',
            ])->keyBy('id');
        $locations = DB::table('locations')
            ->whereIn('id', $locationIds)
            ->get(['id', 'code'])
            ->keyBy('id');

        $details = [];
        foreach ($totals as $key => $total) {
            $batch = $batches->get($total['batch_id']);
            $location = $locations->get($total['location_id']);
            $balance = $this->formatQuantity($total['balance']);
            $ledger = $this->formatQuantity($total['ledger']);
            $details[] = [
                'product_id' => (int) $batch->product_id,
                'sap_material_number' => $batch->sap_material_number,
                'sku' => $batch->sku,
                'product_name' => $batch->name,
                'batch_id' => $total['batch_id'],
                'batch_number' => $batch->batch_number,
                'location_id' => $total['location_id'],
                'location_code' => $location->code,
                'balance' => $balance,
                'ledger' => $ledger,
                'variance' => $this->formatQuantity($total['balance'] - $total['ledger']),
                'matched' => $balance === $ledger,
            ];
        }

        return $details;
    }

    private function quantityUnits(string $quantity): int
    {
        $negative = str_starts_with($quantity, '-');
        $quantity = ltrim($quantity, '+-');
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '000');
        $units = ((int) $whole * 1000) + (int) str_pad(substr($fraction, 0, 3), 3, '0');

        return $negative ? -$units : $units;
    }

    private function formatQuantity(int $units): string
    {
        $absoluteUnits = abs($units);
        $formatted = intdiv($absoluteUnits, 1000).'.'.str_pad((string) ($absoluteUnits % 1000), 3, '0', STR_PAD_LEFT);

        return $units < 0 ? '-'.$formatted : $formatted;
    }
}
