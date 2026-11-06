<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\AuditService;
use App\Services\ProductImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'sort' => [
                'nullable',
                Rule::in(['sku', 'sap_material_number', 'name', 'category', 'unit', 'status', 'created_at']),
            ],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['direction'] ?? 'asc';
        $sortColumn = $sort === 'category' ? 'categories.name' : 'products.'.$sort;

        $products = Product::query()
            ->with('category')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('products.sku', 'like', "%{$search}%")
                        ->orWhere('products.sap_material_number', 'like', "%{$search}%")
                        ->orWhere('products.name', 'like', "%{$search}%")
                        ->orWhere('products.description', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters['category_id'] ?? null,
                fn ($query, string $categoryId) => $query->where('products.category_id', $categoryId),
            )
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('products.status', $status))
            ->when($sort === 'category', fn ($query) => $query->join('categories', 'products.category_id', '=', 'categories.id'))
            ->orderBy($sortColumn, $direction)
            ->select('products.*')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->get();

        $totalCount = Product::query()->count();
        $activeCount = Product::query()->where('status', 'active')->count();
        $categoriesCount = Category::query()->count();
        $inactiveCount = $totalCount - $activeCount;

        $metrics = [
            'total' => $totalCount,
            'active' => $activeCount,
            'categories' => $categoriesCount,
            'inactive' => $inactiveCount,
        ];

        return view('products.index', compact('products', 'categories', 'filters', 'sort', 'direction', 'metrics'));
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);
        $categories = Category::query()->where('status', 'active')->orderBy('name')->get();

        return view('products.form', ['product' => new Product, 'categories' => $categories]);
    }

    public function store(
        StoreProductRequest $request,
        AuditService $auditService,
        ProductImageService $imageService,
    ): RedirectResponse {
        $attributes = $request->safe()->except(['image']);
        $image = $request->file('image');
        $imagePath = $image ? $imageService->store($image) : null;

        try {
            $product = DB::transaction(function () use ($attributes, $imagePath, $auditService): Product {
                $product = Product::query()->create([
                    ...$attributes,
                    'image_path' => $imagePath,
                ]);
                $auditService->record($product, 'created', [], $product->only([
                    'sku',
                    'sap_material_number',
                    'name',
                    'category_id',
                    'unit',
                    'min_level',
                    'reorder_level',
                    'max_level',
                    'status',
                ]));

                return $product;
            });
        } catch (Throwable $exception) {
            $imageService->delete($imagePath);

            throw $exception;
        }

        return to_route('products.show', $product)->with('status', "Product {$product->name} created.");
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);
        $product->load('category');

        return view('products.show', [
            'product' => $product,
            'imageUrl' => $product->image_path
                ? route('products.image', ['filename' => basename($product->image_path)])
                : null,
        ]);
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);
        $categories = Category::query()->where('status', 'active')->orderBy('name')->get();

        return view('products.form', compact('product', 'categories'));
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        AuditService $auditService,
        ProductImageService $imageService,
    ): RedirectResponse {
        $attributes = $request->safe()->except(['image', 'remove_image']);
        $oldValues = $product->only([
            'sku',
            'sap_material_number',
            'name',
            'description',
            'category_id',
            'unit',
            'min_level',
            'reorder_level',
            'max_level',
            'status',
            'image_path',
        ]);
        $oldImagePath = $product->image_path;
        $image = $request->file('image');
        $removeImage = $request->boolean('remove_image');
        $newImagePath = $image ? $imageService->store($image) : ($removeImage ? null : $oldImagePath);
        $attributes['image_path'] = $newImagePath;

        try {
            DB::transaction(function () use ($product, $attributes, $oldValues, $auditService): void {
                $product->update($attributes);
                $auditService->record(
                    $product,
                    'updated',
                    $oldValues,
                    $product->only(array_keys($oldValues)),
                );
            });
        } catch (Throwable $exception) {
            if ($newImagePath !== $oldImagePath) {
                $imageService->delete($newImagePath);
            }

            throw $exception;
        }

        if ($newImagePath !== $oldImagePath) {
            $imageService->delete($oldImagePath);
        }

        return to_route('products.show', $product)->with('status', "Product {$product->name} updated.");
    }

    public function deactivate(Product $product, AuditService $auditService): RedirectResponse
    {
        $this->authorize('update', $product);

        DB::transaction(function () use ($product, $auditService): void {
            $oldStatus = $product->status;
            $product->update(['status' => 'inactive']);
            $auditService->record($product, 'deactivated', ['status' => $oldStatus], ['status' => 'inactive']);
        });

        return to_route('products.index')->with('status', "Product {$product->name} deactivated.");
    }
}
