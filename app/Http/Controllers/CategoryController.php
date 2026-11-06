<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReassignCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $categories = Category::query()
            ->withCount('products')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        $availableCategories = Category::query()->where('status', 'active')->orderBy('name')->get();

        $metrics = [
            'total' => Category::query()->count(),
            'active' => Category::query()->where('status', 'active')->count(),
            'assigned_products' => Product::query()->count(),
            'inactive' => Category::query()->where('status', 'inactive')->count(),
        ];

        return view('categories.index', compact('categories', 'availableCategories', 'metrics'));
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('categories.form', ['category' => new Category]);
    }

    public function store(StoreCategoryRequest $request, AuditService $auditService): RedirectResponse
    {
        $category = DB::transaction(function () use ($request, $auditService): Category {
            $category = Category::query()->create([
                ...$request->validated(),
                'status' => 'active',
            ]);
            $auditService->record($category, 'created', [], $category->only(['name', 'description', 'status']));

            return $category;
        });

        return to_route('categories.index')->with('status', "Category {$category->name} created.");
    }

    public function show(Category $category): View
    {
        $this->authorize('view', $category);
        $products = $category->products()->with('category')->orderBy('name')->paginate(15);

        return view('categories.show', compact('category', 'products'));
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('categories.form', compact('category'));
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category,
        AuditService $auditService,
    ): RedirectResponse {
        $oldValues = $category->only(['name', 'description']);
        $newValues = $request->validated();

        DB::transaction(function () use ($category, $newValues, $oldValues, $auditService): void {
            $category->update($newValues);
            $auditService->record($category, 'updated', $oldValues, $category->only(['name', 'description']));
        });

        return to_route('categories.index')->with('status', "Category {$category->name} updated.");
    }

    public function deactivate(
        ReassignCategoryRequest $request,
        Category $category,
        AuditService $auditService,
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($category, $validated, $auditService): void {
            $this->reassignProducts($category, $validated['reassign_to'] ?? null, $auditService);

            $oldStatus = $category->status;
            $category->update(['status' => 'inactive']);
            $auditService->record($category, 'deactivated', ['status' => $oldStatus], ['status' => 'inactive']);
        });

        return to_route('categories.index')->with('status', "Category {$category->name} deactivated.");
    }

    public function activate(Category $category, AuditService $auditService): RedirectResponse
    {
        $this->authorize('update', $category);

        DB::transaction(function () use ($category, $auditService): void {
            $oldStatus = $category->status;
            $category->update(['status' => 'active']);
            $auditService->record($category, 'activated', ['status' => $oldStatus], ['status' => 'active']);
        });

        return to_route('categories.index')->with('status', "Category {$category->name} activated.");
    }

    public function destroy(
        ReassignCategoryRequest $request,
        Category $category,
        AuditService $auditService,
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($category, $validated, $auditService): void {
            $this->reassignProducts($category, $validated['reassign_to'] ?? null, $auditService);
            $auditService->record($category, 'deleted', $category->only(['name', 'description', 'status']));
            $category->delete();
        });

        return to_route('categories.index')->with('status', 'Category deleted.');
    }

    private function reassignProducts(
        Category $category,
        ?int $targetCategoryId,
        AuditService $auditService,
    ): void {
        if (! $category->products()->exists()) {
            return;
        }

        if ($targetCategoryId === null) {
            throw ValidationException::withMessages([
                'reassign_to' => 'This category has products. Select an active category to reassign them first.',
            ]);
        }

        $category->products()->orderBy('id')->chunkById(100, function ($products) use (
            $targetCategoryId,
            $auditService,
        ): void {
            foreach ($products as $product) {
                $oldCategoryId = $product->category_id;
                $product->update(['category_id' => $targetCategoryId]);
                $auditService->record(
                    $product,
                    'category_reassigned',
                    ['category_id' => $oldCategoryId],
                    ['category_id' => $targetCategoryId],
                );
            }
        });
    }
}
