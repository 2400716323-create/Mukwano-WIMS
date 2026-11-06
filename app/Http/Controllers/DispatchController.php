<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmDispatchRequest;
use App\Http\Requests\CreateDispatchRequest;
use App\Models\DispatchRequest as DispatchRequestModel;
use App\Models\Product;
use App\Services\FefoPickingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DispatchController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', DispatchRequestModel::class);
        $dispatchRequests = DispatchRequestModel::query()
            ->with('product', 'requester', 'dispatcher')
            ->withCount('lines')
            ->latest('requested_at')
            ->paginate(20);

        $metrics = [
            'total' => DispatchRequestModel::query()->count(),
            'pending' => DispatchRequestModel::query()->where('status', 'pending')->count(),
            'dispatched' => DispatchRequestModel::query()->where('status', 'dispatched')->count(),
            'today' => DispatchRequestModel::query()->whereDate('requested_at', today())->count(),
        ];

        return view('dispatch.index', compact('dispatchRequests', 'metrics'));
    }

    public function create(): View
    {
        $this->authorize('create', DispatchRequestModel::class);

        return view('dispatch.create', [
            'products' => Product::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(CreateDispatchRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        $dispatchRequest = DispatchRequestModel::query()->create([
            'product_id' => $attributes['product_id'],
            'requested_quantity' => $attributes['quantity'],
            'requested_by' => $request->user()->id,
            'requested_at' => now(),
        ]);

        return to_route('dispatch-requests.pick-list', $dispatchRequest)
            ->with('status', 'Dispatch request created. Review the FEFO pick list before posting.');
    }

    public function pickList(
        DispatchRequestModel $dispatchRequest,
        FefoPickingService $fefoPickingService,
    ): View|RedirectResponse {
        $this->authorize('view', $dispatchRequest);

        if ($dispatchRequest->status === 'dispatched') {
            return to_route('dispatch-requests.print', $dispatchRequest);
        }

        $dispatchRequest->load('product', 'requester');
        $proposal = $fefoPickingService->propose(
            $dispatchRequest->product_id,
            $dispatchRequest->requested_quantity,
        );

        return view('dispatch.pick-list', [
            'dispatchRequest' => $dispatchRequest,
            'proposal' => $proposal,
            'canDispatch' => request()->user()->can('dispatch', $dispatchRequest),
        ]);
    }

    public function post(
        ConfirmDispatchRequest $request,
        DispatchRequestModel $dispatchRequest,
        FefoPickingService $fefoPickingService,
    ): RedirectResponse {
        $attributes = $request->validated();
        $postedDispatch = $fefoPickingService->post(
            $dispatchRequest,
            $attributes['picks'],
            $attributes['deviation_reason'] ?? null,
            $request->user(),
        );

        return to_route('dispatch-requests.print', $postedDispatch)
            ->with('status', 'Dispatch posted and goods delivery note generated.');
    }

    public function print(DispatchRequestModel $dispatchRequest): View
    {
        $this->authorize('view', $dispatchRequest);
        abort_unless($dispatchRequest->status === 'dispatched', 404);
        $dispatchRequest->load('product', 'requester', 'dispatcher', 'lines.batch', 'lines.location');

        return view('dispatch.print', compact('dispatchRequest'));
    }
}
