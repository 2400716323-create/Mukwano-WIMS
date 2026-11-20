<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\DispatchRequest;
use App\Models\GoodsReceipt;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockKeeperController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        abort_unless(
            $user->isAdministrator() || $user->isWarehouseManager() || $user->isStorekeeper(),
            403,
        );

        $since = Carbon::now()->subDays(30);

        $metrics = [
            'available_units' => (float) StockBalance::query()->where('quantity', '>', 0)->sum('quantity'),
            'incoming_units' => (float) StockMovement::query()->where('movement_type', 'receipt')->where('moved_at', '>=', $since)->sum('quantity'),
            'outgoing_units' => (float) StockMovement::query()->where('movement_type', 'dispatch')->where('moved_at', '>=', $since)->sum('quantity'),
            'pending_dispatches' => DispatchRequest::query()->where('status', 'requested')->count(),
            'put_away_lines' => StockBalance::query()->where('quantity', '>', 0)->count(),
            'open_alerts' => Alert::query()->whereNull('resolved_at')->where('condition_active', true)->whereIn('severity', ['critical', 'high'])->count(),
        ];

        $recentMovements = StockMovement::query()
            ->with(['batch.product', 'fromLocation', 'toLocation', 'performer'])
            ->latest('moved_at')
            ->limit(10)
            ->get();

        $myReceipts = GoodsReceipt::query()
            ->with('supplier')
            ->where('received_by', $user->id)
            ->latest('received_at')
            ->limit(5)
            ->get();

        $myDispatches = DispatchRequest::query()
            ->with('product')
            ->where('requested_by', $user->id)
            ->latest('requested_at')
            ->limit(5)
            ->get();

        $movementBreakdown = StockMovement::query()
            ->where('moved_at', '>=', $since)
            ->select('movement_type', DB::raw('COUNT(*) as movement_count'), DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('movement_type')
            ->get()
            ->keyBy('movement_type');

        return view('stock-keeper.index', compact(
            'metrics',
            'recentMovements',
            'myReceipts',
            'myDispatches',
            'movementBreakdown',
        ));
    }
}
