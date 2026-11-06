<?php

namespace App\Console\Commands;

use App\Services\StockReconciliationService;
use Illuminate\Console\Command;

class ReconcileStockBalances extends Command
{
    protected $signature = 'stock:reconcile';

    protected $description = 'Compare stock balances with the stock movement ledger.';

    public function handle(StockReconciliationService $reconciliationService): int
    {
        $result = $reconciliationService->reconcile();
        if ($result['matched']) {
            $this->info("Stock reconciliation passed for {$result['checked']} batch/location balances.");

            return self::SUCCESS;
        }

        $this->error('Stock reconciliation found mismatches:');
        $this->table(
            ['Batch ID', 'Location ID', 'Balance', 'Movement ledger'],
            array_map(
                fn (array $mismatch): array => [
                    $mismatch['batch_id'],
                    $mismatch['location_id'],
                    $mismatch['balance'],
                    $mismatch['ledger'],
                ],
                $result['mismatches'],
            ),
        );

        return self::FAILURE;
    }
}
