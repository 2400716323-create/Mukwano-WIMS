<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_pages_require_an_authenticated_user(): void
    {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->get(route('suppliers.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Products');
    }

    public function test_category_crud_search_product_count_and_audit_logging(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('categories.store'), [
            'name' => 'Electrical',
            'description' => 'Electrical materials',
        ])->assertRedirect(route('categories.index'));

        $category = Category::query()->where('name', 'Electrical')->firstOrFail();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->get(route('categories.index', ['search' => 'Electrical']))
            ->assertOk()
            ->assertSee('Electrical')
            ->assertSee('1');

        $this->put(route('categories.update', $category), [
            'name' => 'Electrical supplies',
            'description' => 'Updated description',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Electrical supplies',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Category::class,
            'auditable_id' => $category->id,
            'event' => 'created',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Category::class,
            'auditable_id' => $category->id,
            'event' => 'updated',
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => $category->id]);
    }

    public function test_category_cannot_be_deactivated_or_deleted_with_products_without_reassignment(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $target = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->patch(route('categories.deactivate', $category))
            ->assertSessionHasErrors('reassign_to');
        $this->assertSame('active', $category->fresh()->status);

        $this->delete(route('categories.destroy', $category))
            ->assertSessionHasErrors('reassign_to');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);

        $this->patch(route('categories.deactivate', $category), ['reassign_to' => $target->id])
            ->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => $target->id]);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
            'event' => 'category_reassigned',
        ]);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_reassignment_target_must_be_another_active_category(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $inactive = Category::factory()->create(['status' => 'inactive']);

        $this->patch(route('categories.deactivate', $category), ['reassign_to' => $category->id])
            ->assertSessionHasErrors('reassign_to');
        $this->patch(route('categories.deactivate', $category), ['reassign_to' => $inactive->id])
            ->assertSessionHasErrors('reassign_to');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => $category->id]);
    }

    public function test_products_validate_unique_skus_and_threshold_order(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();
        Product::factory()->create(['sku' => 'DUP-1', 'category_id' => $category->id]);

        $this->post(route('products.store'), $this->productData($category, [
            'sku' => 'DUP-1',
        ]))->assertSessionHasErrors('sku');

        $this->post(route('products.store'), $this->productData($category, [
            'sku' => 'LEVEL-1',
            'min_level' => 10,
            'reorder_level' => 2,
            'max_level' => 20,
        ]))->assertSessionHasErrors('min_level');

        $this->assertDatabaseMissing('products', ['sku' => 'LEVEL-1']);
    }

    public function test_product_search_filters_sorting_and_deactivation_keep_the_product_record(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $anotherCategory = Category::factory()->create();
        $product = Product::factory()->create([
            'sku' => 'FILTER-1',
            'name' => 'Copper Wire',
            'category_id' => $category->id,
        ]);
        Product::factory()->create([
            'sku' => 'FILTER-2',
            'name' => 'Steel Rod',
            'category_id' => $anotherCategory->id,
        ]);

        $this->get(route('products.index', [
            'search' => 'Copper',
            'category_id' => $category->id,
            'status' => 'active',
            'sort' => 'category',
        ]))
            ->assertOk()
            ->assertSee('Copper Wire')
            ->assertDontSee('Steel Rod');

        $this->patch(route('products.deactivate', $product))
            ->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
            'event' => 'deactivated',
        ]);
        $this->delete('/products/'.$product->id)->assertStatus(405);
    }

    public function test_product_listing_sorts_columns_and_paginates_results(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();

        for ($number = 1; $number <= 16; $number++) {
            Product::factory()->create([
                'sku' => sprintf('PAGE-%02d', $number),
                'name' => sprintf('Paged item %02d', $number),
                'category_id' => $category->id,
            ]);
        }

        $this->get(route('products.index', [
            'search' => 'Paged item',
            'sort' => 'name',
            'direction' => 'asc',
        ]))
            ->assertOk()
            ->assertSee('Paged item 01')
            ->assertSee('Paged item 15')
            ->assertDontSee('Paged item 16');

        $this->get(route('products.index', [
            'search' => 'Paged item',
            'sort' => 'name',
            'direction' => 'desc',
        ]))
            ->assertSeeInOrder(['Paged item 16', 'Paged item 02'])
            ->assertDontSee('Paged item 01');

        $this->get(route('products.index', [
            'search' => 'Paged item',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 2,
        ]))
            ->assertSee('Paged item 16')
            ->assertDontSee('Paged item 01');
    }

    public function test_product_image_upload_replace_remove_and_invalid_content_rules(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();

        $this->post(route('products.store'), $this->productData($category, [
            'sku' => 'IMAGE-1',
            'image' => $this->tinyPng('original.png'),
        ]))->assertRedirect();

        $product = Product::query()->where('sku', 'IMAGE-1')->firstOrFail();
        $originalImagePath = $product->image_path;
        $this->assertNotSame('original.png', basename($originalImagePath));
        Storage::disk('public')->assertExists($originalImagePath);
        $this->get(route('products.image', ['filename' => basename($originalImagePath)]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->put(route('products.update', $product), $this->productData($category, [
            'sku' => 'IMAGE-1',
            'image' => $this->tinyPng('replacement.png'),
        ]))->assertRedirect();

        $replacementImagePath = $product->fresh()->image_path;
        $this->assertNotSame($originalImagePath, $replacementImagePath);
        Storage::disk('public')->assertMissing($originalImagePath);
        Storage::disk('public')->assertExists($replacementImagePath);

        $this->put(route('products.update', $product), $this->productData($category, [
            'sku' => 'IMAGE-1',
            'remove_image' => '1',
        ]))->assertRedirect();

        $this->assertNull($product->fresh()->image_path);
        Storage::disk('public')->assertMissing($replacementImagePath);

        $this->post(route('products.store'), $this->productData($category, [
            'sku' => 'BAD-IMAGE',
            'image' => UploadedFile::fake()->createWithContent('not-an-image.png', 'plain text, not image data'),
        ]))->assertSessionHasErrors('image');

        $this->post(route('products.store'), $this->productData($category, [
            'sku' => 'BAD-TYPE',
            'image' => UploadedFile::fake()->createWithContent('document.txt', 'plain text, not image data'),
        ]))->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('products', ['sku' => 'BAD-IMAGE']);
        $this->assertDatabaseMissing('products', ['sku' => 'BAD-TYPE']);
    }

    public function test_oversized_product_image_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();

        $this->post(route('products.store'), $this->productData($category, [
            'sku' => 'TOO-LARGE',
            'image' => UploadedFile::fake()->create('oversized.jpg', 5121, 'image/jpeg'),
        ]))->assertSessionHasErrors('image');
    }

    public function test_supplier_crud_search_status_and_audit_logging(): void
    {
        $this->actingAs(User::factory()->create());
        $payload = [
            'name' => 'Acme Metals',
            'code' => 'ACME-1',
            'contact_person' => 'Amina',
            'phone' => '+256700000000',
            'email' => 'amina@example.test',
            'address' => 'Kampala',
            'notes' => 'Primary supplier',
            'status' => 'active',
        ];

        $this->post(route('suppliers.store'), $payload)->assertRedirect();
        $supplier = Supplier::query()->where('code', 'ACME-1')->firstOrFail();
        $this->get(route('suppliers.index', ['search' => 'Amina', 'status' => 'active']))
            ->assertOk()
            ->assertSee('Acme Metals');

        $this->put(route('suppliers.update', $supplier), [
            ...$payload,
            'name' => 'Acme Steel',
        ])->assertRedirect(route('suppliers.show', $supplier));

        $this->patch(route('suppliers.deactivate', $supplier))->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Acme Steel', 'status' => 'inactive']);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Supplier::class,
            'auditable_id' => $supplier->id,
            'event' => 'created',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Supplier::class,
            'auditable_id' => $supplier->id,
            'event' => 'updated',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Supplier::class,
            'auditable_id' => $supplier->id,
            'event' => 'deactivated',
        ]);

        $this->delete(route('suppliers.destroy', $supplier))->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Supplier::class,
            'auditable_id' => $supplier->id,
            'event' => 'deleted',
        ]);
    }

    public function test_supplier_code_and_email_are_validated(): void
    {
        $this->actingAs(User::factory()->create());
        Supplier::factory()->create(['code' => 'DUP-SUP']);

        $this->post(route('suppliers.store'), [
            'name' => 'Another supplier',
            'code' => 'DUP-SUP',
            'email' => 'not-an-email',
            'status' => 'active',
        ])->assertSessionHasErrors(['code', 'email']);
    }

    public function test_product_csv_import_and_export(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $csv = implode(',', [
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
        ])."\n";
        $csv .= implode(',', ['CSV-1', 'SAP-1', 'CSV Product', 'A useful item', $category->id, 'each', 1, 2, 5, 'active'])."\n";

        $response = $this->post(route('products.import'), [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ])->assertOk();
        $importId = $response->viewData('import_id');
        $this->assertDatabaseMissing('products', ['sku' => 'CSV-1']);

        $this->post(route('erp-exchange.products.validate', $importId), [
            'mapping' => [
                'sku' => 'sku',
                'sap_material_number' => 'sap_material_number',
                'name' => 'name',
                'description' => 'description',
                'category_id' => 'category_id',
                'unit' => 'unit',
                'min_level' => 'min_level',
                'reorder_level' => 'reorder_level',
                'max_level' => 'max_level',
                'status' => 'status',
            ],
        ])->assertOk()->assertSee('Ready to create');

        $this->post(route('erp-exchange.products.confirm', $importId))
            ->assertRedirect(route('erp-exchange.products.show', $importId));

        $this->assertDatabaseHas('products', [
            'sku' => 'CSV-1',
            'sap_material_number' => 'SAP-1',
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Product::class,
            'event' => 'imported',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'csv_imported',
            'user_id' => auth()->id(),
        ]);

        $this->get(route('products.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload();
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'csv_exported',
            'user_id' => auth()->id(),
            'new_values->resource' => 'products',
        ]);
    }

    public function test_product_csv_import_rejects_duplicate_and_invalid_rows(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $header = 'sku,sap_material_number,name,description,category_id,unit,min_level,reorder_level,max_level,status';
        $row = implode(',', ['DUP-CSV', '', 'CSV Product', '', $category->id, 'each', 1, 2, 5, 'active']);

        $importId = $this->post(route('products.import'), [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $header."\n".$row."\n".$row),
        ])->assertOk()->viewData('import_id');

        $duplicateReview = $this->post(route('erp-exchange.products.validate', $importId), [
            'mapping' => [
                'sku' => 'sku',
                'sap_material_number' => 'sap_material_number',
                'name' => 'name',
                'description' => 'description',
                'category_id' => 'category_id',
                'unit' => 'unit',
                'min_level' => 'min_level',
                'reorder_level' => 'reorder_level',
                'max_level' => 'max_level',
                'status' => 'status',
            ],
        ])->assertOk();
        $this->assertNotEmpty($duplicateReview->viewData('staged')['validated_rows'][0]['errors']);

        $invalidHeaderImport = $this->post(route('products.import'), [
            'file' => UploadedFile::fake()->createWithContent('wrong.csv', 'sku,name'."\n".'SKU,Name'),
        ])->assertOk()->viewData('import_id');
        $this->post(route('erp-exchange.products.validate', $invalidHeaderImport), [
            'mapping' => ['sku' => 'sku', 'name' => 'name'],
        ])->assertSessionHasErrors('mapping.sap_material_number');

        $this->assertDatabaseMissing('products', ['sku' => 'DUP-CSV']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productData(Category $category, array $overrides = []): array
    {
        return [
            'sku' => 'TEST-1',
            'sap_material_number' => null,
            'name' => 'Test product',
            'description' => 'Test product description',
            'category_id' => $category->id,
            'unit' => 'each',
            'min_level' => 1,
            'reorder_level' => 2,
            'max_level' => 5,
            'status' => 'active',
            ...$overrides,
        ];
    }

    private function tinyPng(string $filename): UploadedFile
    {
        $imageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jXssAAAAASUVORK5CYII=', true);
        self::assertIsString($imageData);

        return UploadedFile::fake()->createWithContent($filename, $imageData);
    }
}
