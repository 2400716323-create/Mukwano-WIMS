<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportsAndErpExchangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_reports_match_the_stock_ledger_and_export_with_the_same_filters(): void
    {
        $this->actingAs(User::factory()->create());
        [$product, $batch, $location] = $this->createLedgerStock('ERP-100', 'LEDGER-100', '5.250');
        Product::factory()->create([
            'category_id' => $product->category_id,
            'sku' => 'UNRELATED-1',
            'name' => 'Unrelated product',
        ]);

        $this->assertTrue(app(StockReconciliationService::class)->reconcile()['matched']);

        $this->get(route('reports.show', [
            'report' => 'stock-on-hand',
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'location_id' => $location->id,
        ]))
            ->assertOk()
            ->assertSee('5.250')
            ->assertDontSee('<td>Unrelated product</td>', false);

        $csvResponse = $this->get(route('reports.csv', [
            'report' => 'stock-on-hand',
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'location_id' => $location->id,
        ]))->assertOk();
        $this->assertStringContainsString('5.250', $csvResponse->streamedContent());
        $this->assertStringContainsString('TOTAL (1 rows)', $csvResponse->streamedContent());
        $this->assertStringNotContainsString('UNRELATED-1', $csvResponse->streamedContent());
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'csv_exported',
            'new_values->resource' => 'stock-on-hand',
        ]);

        $pdfResponse = $this->get(route('reports.pdf', [
            'report' => 'stock-on-hand',
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'location_id' => $location->id,
        ]))->assertOk();
        $this->assertStringContainsString('LEDGER-100', $pdfResponse->streamedContent());
        $this->assertStringNotContainsString('UNRELATED-1', $pdfResponse->streamedContent());

        $this->get(route('reports.show', ['report' => 'ira', 'product_id' => $product->id]))
            ->assertOk()
            ->assertSee('Yes')
            ->assertSee('5.250');

        $reconciliationCsv = $this->get(route('erp-exchange.reconciliation.csv', [
            'product_id' => $product->id,
        ]))->assertOk()->streamedContent();
        $this->assertStringContainsString('exported_at', $reconciliationCsv);
        $this->assertStringContainsString('column_definitions', $reconciliationCsv);
        $this->assertStringContainsString('balance_quantity=current stock balance quantity', $reconciliationCsv);
        $this->assertStringContainsString('stock-pair', $reconciliationCsv);
        $this->assertStringNotContainsString('UNRELATED-1', $reconciliationCsv);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'csv_exported',
            'new_values->resource' => 'stock_reconciliation',
        ]);
    }

    public function test_invalid_product_rows_are_rejected_without_blocking_valid_import_rows(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $this->actingAs($user);

        $upload = UploadedFile::fake()->createWithContent(
            'products.csv',
            "MATNR,Name,Category,Unit\nSAP-VALID,Valid product,{$category->id},EA\nSAP-INVALID,,{$category->id},EA\n",
        );
        $mappingPage = $this->post(route('erp-exchange.products.preview'), ['file' => $upload])
            ->assertOk()
            ->assertSee('Map product-master columns');
        $importId = $mappingPage->viewData('import_id');

        $validationPage = $this->post(route('erp-exchange.products.validate', $importId), [
            'mapping' => [
                'sap_material_number' => 'MATNR',
                'name' => 'Name',
                'category_id' => 'Category',
                'unit' => 'Unit',
            ],
        ])->assertOk()->assertSee('Valid rows: 1')->assertSee('Rows with errors: 1');
        $this->assertNotEmpty($validationPage->viewData('staged')['validated_rows'][1]['errors']);

        $this->post(route('erp-exchange.products.confirm', $importId))
            ->assertRedirect(route('erp-exchange.products.show', $importId));

        $this->get(route('erp-exchange.products.show', $importId))
            ->assertOk()
            ->assertSee('Created: 1')
            ->assertSee('Rejected: 1');
        $this->assertDatabaseHas('products', ['sap_material_number' => 'SAP-VALID', 'name' => 'Valid product']);
        $this->assertDatabaseMissing('products', ['sap_material_number' => 'SAP-INVALID']);
        $this->assertDatabaseCount('stock_balances', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        $errorCsv = $this->get(route('erp-exchange.products.errors', $importId))->assertOk();
        $this->assertStringContainsString('SAP-INVALID', $errorCsv->streamedContent());
        $this->assertStringNotContainsString('SAP-VALID', $errorCsv->streamedContent());
    }

    public function test_duplicate_sap_material_numbers_are_flagged_and_not_imported(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $upload = UploadedFile::fake()->createWithContent(
            'duplicates.csv',
            "Material,Name,Category,Unit\nSAP-DUP,First,{$category->id},EA\nSAP-DUP,Second,{$category->id},EA\n",
        );
        $importId = $this->post(route('erp-exchange.products.preview'), ['file' => $upload])
            ->assertOk()
            ->viewData('import_id');

        $validation = $this->post(route('erp-exchange.products.validate', $importId), [
            'mapping' => [
                'sap_material_number' => 'Material',
                'name' => 'Name',
                'category_id' => 'Category',
                'unit' => 'Unit',
            ],
        ])->assertOk();

        foreach ($validation->viewData('staged')['validated_rows'] as $row) {
            $this->assertContains('Duplicate SAP material number in this CSV file.', $row['errors']);
        }

        $this->post(route('erp-exchange.products.confirm', $importId))
            ->assertRedirect(route('erp-exchange.products.show', $importId));
        $this->assertDatabaseMissing('products', ['sap_material_number' => 'SAP-DUP']);
    }

    public function test_product_master_update_never_changes_existing_stock_balances(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        [$product, $batch, $location] = $this->createLedgerStock('SAP-EXISTING', 'SKU-EXISTING', '8.125');
        $upload = UploadedFile::fake()->createWithContent(
            'master.csv',
            "Material,Name\nSAP-EXISTING,Updated product name\n",
        );
        $importId = $this->post(route('erp-exchange.products.preview'), ['file' => $upload])
            ->assertOk()
            ->viewData('import_id');

        $this->post(route('erp-exchange.products.validate', $importId), [
            'mapping' => [
                'sap_material_number' => 'Material',
                'name' => 'Name',
            ],
        ])->assertOk()->assertSee('Ready to update');

        $this->post(route('erp-exchange.products.confirm', $importId))
            ->assertRedirect(route('erp-exchange.products.show', $importId));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated product name',
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'batch_id' => $batch->id,
            'location_id' => $location->id,
            'quantity' => '8.125',
        ]);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertTrue(app(StockReconciliationService::class)->reconcile()['matched']);
    }

    public function test_all_report_queries_render_and_pdf_download_is_a_pdf(): void
    {
        $this->actingAs(User::factory()->create());
        $reports = [
            'stock-on-hand',
            'goods-received',
            'goods-dispatched',
            'movement',
            'transfers',
            'expiry-ageing',
            'low-stock',
            'cycle-count',
            'ira',
            'purchase-orders',
            'audit-log',
        ];

        $this->get(route('reports.index'))->assertOk();
        foreach ($reports as $report) {
            $this->get(route('reports.show', $report))->assertOk();
        }

        $pdfResponse = $this->get(route('reports.pdf', 'stock-on-hand'))->assertOk();
        $pdf = $pdfResponse->streamedContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
        preg_match('/startxref\n(\d+)\n%%EOF/', $pdf, $xref);
        $this->assertSame("xref\n", substr($pdf, (int) ($xref[1] ?? 0), 5));
    }

    /**
     * @return array{Product, Batch, Location}
     */
    private function createLedgerStock(string $sapNumber, string $sku, string $quantity): array
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sap_material_number' => $sapNumber,
            'sku' => $sku,
            'name' => 'Ledger product',
        ]);
        $location = Location::query()->create([
            'type' => 'bin',
            'name' => 'Test bin',
            'code' => 'BIN-TEST',
            'status' => 'active',
        ]);
        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'batch_number' => 'LOT-100',
        ]);
        StockBalance::query()->create([
            'batch_id' => $batch->id,
            'location_id' => $location->id,
            'quantity' => $quantity,
        ]);
        StockMovement::query()->create([
            'batch_id' => $batch->id,
            'to_location_id' => $location->id,
            'movement_type' => 'opening_balance',
            'quantity' => $quantity,
            'moved_at' => now(),
        ]);

        return [$product, $batch, $location];
    }
}
