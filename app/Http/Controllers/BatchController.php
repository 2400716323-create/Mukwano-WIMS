<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(
            $request->user()->isAdministrator() ||
            $request->user()->isWarehouseManager() ||
            $request->user()->isStorekeeper() ||
            $request->user()->isProcurementOfficer() ||
            $request->user()->isDispatchOfficer() ||
            $request->user()->isAuditor(),
            403
        );

        $query = Batch::query()
            ->with(['product.category'])
            ->withSum('stockBalances as total_quantity', 'quantity');

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($term): void {
                $q->where('batch_number', 'like', $term)
                    ->orWhereHas('product', function ($pq) use ($term): void {
                        $pq->where('name', 'like', $term)
                            ->orWhere('sku', 'like', $term);
                    });
            });
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->input('expiry_status') === 'expired') {
            $query->whereNotNull('expires_at')->where('expires_at', '<', today());
        } elseif ($request->input('expiry_status') === 'near_expiry') {
            $query->whereNotNull('expires_at')
                ->where('expires_at', '>=', today())
                ->where('expires_at', '<=', today()->addDays(30));
        }

        $batches = $query->latest('created_at')->paginate(20)->withQueryString();
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        $metrics = [
            'total' => Batch::query()->count(),
            'active_stock' => Batch::query()->whereHas('stockBalances', fn ($q) => $q->where('quantity', '>', 0))->count(),
            'near_expiry' => Batch::query()->whereNotNull('expires_at')->where('expires_at', '>=', today())->where('expires_at', '<=', today()->addDays(30))->count(),
            'expired' => Batch::query()->whereNotNull('expires_at')->where('expires_at', '<', today())->count(),
        ];

        return view('batches.index', compact('batches', 'products', 'metrics'));
    }

    public function show(Request $request, Batch $batch): View
    {
        abort_unless(
            $request->user()->isAdministrator() ||
            $request->user()->isWarehouseManager() ||
            $request->user()->isStorekeeper() ||
            $request->user()->isProcurementOfficer() ||
            $request->user()->isDispatchOfficer() ||
            $request->user()->isAuditor(),
            403
        );

        $batch->load(['product.category', 'stockBalances.location.parent.parent']);

        $balances = $batch->stockBalances()
            ->with('location.parent.parent')
            ->where('quantity', '>', 0)
            ->get();

        $movements = StockMovement::query()
            ->where('batch_id', $batch->id)
            ->with(['fromLocation', 'toLocation', 'performer'])
            ->latest('moved_at')
            ->paginate(15);

        $totalOnHand = $balances->sum(fn ($b) => (float) $b->quantity);

        return view('batches.show', compact('batch', 'balances', 'movements', 'totalOnHand'));
    }
}
