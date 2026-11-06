<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\StockBalance;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PutAwayController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(
            $request->user()->isAdministrator() ||
            $request->user()->isWarehouseManager() ||
            $request->user()->isStorekeeper(),
            403
        );

        $query = StockBalance::query()
            ->with(['batch.product.category', 'location.parent.parent'])
            ->where('quantity', '>', 0);

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($term): void {
                $q->whereHas('batch.product', function ($pq) use ($term): void {
                    $pq->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                })->orWhereHas('batch', function ($bq) use ($term): void {
                    $bq->where('batch_number', 'like', $term);
                })->orWhereHas('location', function ($lq) use ($term): void {
                    $lq->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term);
                });
            });
        }

        $stockBalances = $query->latest('updated_at')->paginate(20)->withQueryString();

        $metrics = [
            'total_tasks' => StockBalance::query()->where('quantity', '>', 0)->count(),
            'total_units' => StockBalance::query()->where('quantity', '>', 0)->sum('quantity'),
            'bins_count' => Location::query()->where('type', 'bin')->where('status', 'active')->count(),
            'distinct_batches' => StockBalance::query()->where('quantity', '>', 0)->distinct('batch_id')->count('batch_id'),
        ];

        return view('put-away.index', compact('stockBalances', 'metrics'));
    }

    public function show(Request $request, StockBalance $stockBalance): View
    {
        abort_unless(
            $request->user()->isAdministrator() ||
            $request->user()->isWarehouseManager() ||
            $request->user()->isStorekeeper(),
            403
        );

        $stockBalance->load(['batch.product.category', 'location.parent.parent']);

        $destinationBins = Location::query()
            ->where('type', 'bin')
            ->where('status', 'active')
            ->whereKeyNot($stockBalance->location_id)
            ->with('parent.parent')
            ->orderBy('code')
            ->get()
            ->filter(fn (Location $location) => $location->isOperational());

        return view('put-away.show', compact('stockBalance', 'destinationBins'));
    }
}
