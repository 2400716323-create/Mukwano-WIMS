<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $suppliers = Supplier::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total' => Supplier::query()->count(),
            'active' => Supplier::query()->where('status', 'active')->count(),
            'inactive' => Supplier::query()->where('status', 'inactive')->count(),
            'purchase_orders' => PurchaseOrder::query()->count(),
        ];

        return view('suppliers.index', compact('suppliers', 'filters', 'metrics'));
    }

    public function create(): View
    {
        $this->authorize('create', Supplier::class);

        return view('suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(StoreSupplierRequest $request, AuditService $auditService): RedirectResponse
    {
        $supplier = DB::transaction(function () use ($request, $auditService): Supplier {
            $supplier = Supplier::query()->create($request->validated());
            $auditService->record($supplier, 'created', [], $supplier->only([
                'name',
                'code',
                'contact_person',
                'phone',
                'email',
                'address',
                'notes',
                'status',
            ]));

            return $supplier;
        });

        return to_route('suppliers.show', $supplier)->with('status', "Supplier {$supplier->name} created.");
    }

    public function show(Supplier $supplier): View
    {
        $this->authorize('view', $supplier);

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', $supplier);

        return view('suppliers.form', compact('supplier'));
    }

    public function update(
        UpdateSupplierRequest $request,
        Supplier $supplier,
        AuditService $auditService,
    ): RedirectResponse {
        $oldValues = $supplier->only([
            'name',
            'code',
            'contact_person',
            'phone',
            'email',
            'address',
            'notes',
            'status',
        ]);

        DB::transaction(function () use ($supplier, $request, $oldValues, $auditService): void {
            $supplier->update($request->validated());
            $auditService->record(
                $supplier,
                'updated',
                $oldValues,
                $supplier->only(array_keys($oldValues)),
            );
        });

        return to_route('suppliers.show', $supplier)->with('status', "Supplier {$supplier->name} updated.");
    }

    public function deactivate(Supplier $supplier, AuditService $auditService): RedirectResponse
    {
        $this->authorize('update', $supplier);

        DB::transaction(function () use ($supplier, $auditService): void {
            $oldStatus = $supplier->status;
            $supplier->update(['status' => 'inactive']);
            $auditService->record($supplier, 'deactivated', ['status' => $oldStatus], ['status' => 'inactive']);
        });

        return to_route('suppliers.index')->with('status', "Supplier {$supplier->name} deactivated.");
    }

    public function destroy(Supplier $supplier, AuditService $auditService): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        DB::transaction(function () use ($supplier, $auditService): void {
            $auditService->record($supplier, 'deleted', $supplier->only([
                'name',
                'code',
                'contact_person',
                'phone',
                'email',
                'address',
                'notes',
                'status',
            ]));
            $supplier->delete();
        });

        return to_route('suppliers.index')->with('status', 'Supplier deleted.');
    }
}
