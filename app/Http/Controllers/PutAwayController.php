<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\PutAwayTask;
use App\Models\StockBalance;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PutAwayController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user->isAdministrator()
            || $user->isWarehouseManager()
            || $user->isStorekeeper(),
            403
        );

        // Only show stock in active, operational bins,
        // Receiving Areas, and Staging Areas.
        $query = $this->eligibleStockBalancesQuery();

        $query->with([
            'batch.product.category',
            'location.parent.parent',
        ]);

        // Search by product name, SKU, batch number, or location.
        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';

            $query->where(function ($q) use ($term): void {
                $q->whereHas('batch.product', function ($pq) use ($term): void {
                    $pq->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                })
                ->orWhereHas('batch', function ($bq) use ($term): void {
                    $bq->where('batch_number', 'like', $term);
                })
                ->orWhereHas('location', function ($lq) use ($term): void {
                    $lq->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term);
                });
            });
        }

        $stockBalances = $query
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        // Retrieve put-away task history.
        $tasksQuery = PutAwayTask::query()
            ->with([
                'batch.product',
                'sourceLocation',
                'destinationLocation',
                'assignee',
                'creator',
                'completer',
            ])
            ->latest();

        // Storekeepers see only their assigned tasks.
        // Supervisors can see all tasks.
        if ($user->isStorekeeper() && ! $user->isSupervisor()) {
            $tasksQuery->where('assigned_to', $user->id);
        }

        $putAwayTasks = $tasksQuery->limit(100)->get();

        $canManageTasks = $user->isSupervisor();

        // Calculate statistics using eligible stock balances.
        $metrics = [
            'total_tasks' => $this->eligibleStockBalancesQuery()->count(),

            'total_units' => $this->eligibleStockBalancesQuery()
                ->sum('quantity'),

            'bins_count' => Location::query()
                ->where('type', 'bin')
                ->where('status', 'active')
                ->get()
                ->filter(fn (Location $location) => $location->isOperational())
                ->count(),

            'distinct_batches' => $this->eligibleStockBalancesQuery()
                ->distinct()
                ->count('batch_id'),
        ];

        return view('put-away.index', compact(
            'stockBalances',
            'metrics',
            'putAwayTasks',
            'canManageTasks'
        ));
    }

    public function show(
        Request $request,
        StockBalance $stockBalance
    ): View {
        $user = $request->user();

        abort_unless(
            $user->isAdministrator()
            || $user->isWarehouseManager()
            || $user->isStorekeeper(),
            403
        );

        $stockBalance->load([
            'batch.product.category',
            'location.parent.parent',
        ]);

        // A direct put-away page requires positive stock
        // in an active operational source location.
        abort_unless(
            (float) $stockBalance->quantity > 0
            && $this->isValidPutAwaySource($stockBalance->location),
            404
        );

        // Destination must always be an operational storage bin.
        $destinationBins = Location::query()
            ->where('type', 'bin')
            ->where('status', 'active')
            ->whereKeyNot($stockBalance->location_id)
            ->with('parent.parent')
            ->orderBy('code')
            ->get()
            ->filter(
                fn (Location $location) => $location->isOperational()
            )
            ->values();

        return view('put-away.show', compact(
            'stockBalance',
            'destinationBins'
        ));
    }

    /**
     * Get positive stock balances from valid put-away source locations.
     */
    private function eligibleStockBalancesQuery()
    {
        return StockBalance::query()
            ->where('quantity', '>', 0)
            ->whereHas('location', function ($location): void {
                $location
                    ->where('status', 'active')
                    ->where(function ($types): void {

                        // Storage bin:
                        // Bin -> Rack -> Zone -> Warehouse.
                        $types->where(function ($bin): void {
                            $bin->where('type', 'bin')
                                ->whereHas('parent', function ($rack): void {
                                    $rack->where('type', 'rack')
                                        ->where('status', 'active')
                                        ->whereHas('parent', function ($zone): void {
                                            $zone->where('type', 'zone')
                                                ->where('status', 'active')
                                                ->whereHas('parent', function ($warehouse): void {
                                                    $warehouse->where('type', 'warehouse')
                                                        ->where('status', 'active');
                                                });
                                        });
                                });
                        })

                        // Receiving or Staging:
                        // Receiving/Staging -> Warehouse.
                        ->orWhere(function ($receivingOrStaging): void {
                            $receivingOrStaging
                                ->whereIn('type', ['receiving', 'staging'])
                                ->whereHas('parent', function ($warehouse): void {
                                    $warehouse->where('type', 'warehouse')
                                        ->where('status', 'active');
                                });
                        });
                    });
            });
    }

    /**
     * Check whether a location can be used as a put-away source.
     */
    private function isValidPutAwaySource(?Location $location): bool
    {
        return $location !== null
            && in_array($location->type, ['bin', 'receiving', 'staging'], true)
            && $location->isOperational();
    }
}
