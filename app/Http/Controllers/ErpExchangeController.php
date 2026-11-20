<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Services\AuditService;
use App\Services\ErpProductImportService;
use App\Services\StockReconciliationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ErpExchangeController extends Controller
{
    public function index(): View
    {
        $user = request()->user();

        abort_unless(
            $user !== null && (
                $user->can('import', Product::class)
                || $user->hasPermission(
                    'View ERP exchange logs and reconciliation summaries'
                )
            ),
            403
        );

        return view('erp-exchange.index', [
            'categories' => Category::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function preview(
        Request $request,
        ErpProductImportService $importService,
    ): View {
        $this->authorize('import', Product::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $staged = $importService->stage(
            $validated['file'],
            (int) $request->user()->id
        );

        $fields = $importService->fields();

        return view('erp-exchange.mapping', [
            ...$staged,
            'fields' => $fields,
            'suggested_mapping' => $this->suggestedMapping(
                $fields,
                $staged['headers']
            ),
        ]);
    }

    public function validateMapping(
        Request $request,
        string $importId,
        ErpProductImportService $importService,
    ): View {
        $this->authorize('import', Product::class);

        $fields = $importService->fields();

        $validated = $request->validate([
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'string', 'max:100'],
        ]);

        $mapping = array_intersect_key(
            $validated['mapping'],
            array_flip($fields)
        );

        $staged = $importService->validateMapping(
            $importId,
            (int) $request->user()->id,
            $mapping
        );

        return view('erp-exchange.validation', [
            'importId' => $importId,
            'staged' => $staged,
            'fields' => $fields,
            'confirmed' => false,
        ]);
    }

    public function confirm(
        Request $request,
        string $importId,
        ErpProductImportService $importService,
        AuditService $auditService,
    ): RedirectResponse {
        $this->authorize('import', Product::class);

        $importService->confirm(
            $importId,
            (int) $request->user()->id,
            $auditService
        );

        return to_route('erp-exchange.products.show', $importId)
            ->with('status', 'Product master import confirmed.');
    }

    public function show(
        Request $request,
        string $importId,
        ErpProductImportService $importService
    ): View {
        $this->authorize('import', Product::class);

        $staged = $importService->import(
            $importId,
            (int) $request->user()->id
        );

        abort_unless(
            $staged['status'] === 'confirmed',
            409,
            'This import has not been confirmed.'
        );

        return view('erp-exchange.validation', [
            'importId' => $importId,
            'staged' => $staged,
            'fields' => $importService->fields(),
            'confirmed' => true,
        ]);
    }

    public function errors(
        Request $request,
        string $importId,
        ErpProductImportService $importService,
        AuditService $auditService,
    ): StreamedResponse {
        $this->authorize('import', Product::class);

        $staged = $importService->import(
            $importId,
            (int) $request->user()->id
        );

        $rows = $staged['validated_rows'] ?? [];

        $auditService->recordActivity('csv_exported', null, [], [
            'resource' => 'product_import_errors',
            'import_id' => $importId,
            'row_count' => count(array_filter(
                $rows,
                static fn (array $row): bool => $row['errors'] !== []
            )),
        ]);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');

            fputcsv(
                $output,
                ['row_number', 'sap_material_number', 'sku', 'name', 'errors'],
                ',',
                '"',
                ''
            );

            foreach ($rows as $row) {
                if ($row['errors'] === []) {
                    continue;
                }

                fputcsv($output, [
                    $row['row_number'],
                    $this->safeCsvValue(
                        (string) ($row['data']['sap_material_number'] ?? '')
                    ),
                    $this->safeCsvValue(
                        (string) ($row['data']['sku'] ?? '')
                    ),
                    $this->safeCsvValue(
                        (string) ($row['data']['name'] ?? '')
                    ),
                    $this->safeCsvValue(
                        implode('; ', $row['errors'])
                    ),
                ], ',', '"', '');
            }

            fclose($output);
        }, "product-import-{$importId}-errors.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function reconciliationCsv(
        Request $request,
        StockReconciliationService $reconciliationService,
        AuditService $auditService,
    ): StreamedResponse {
        $this->authorizeReconciliationExport();

        $filters = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $timestamp = now()->toIso8601String();

        $columnDefinitions = [
            'sap_material_number' => 'ERP SAP material number',
            'sku' => 'WIMS product SKU',
            'product_name' => 'product description',
            'batch_number' => 'stock batch identifier',
            'location_code' => 'warehouse location code',
            'balance_quantity' => 'current stock balance quantity',
            'ledger_quantity' => 'quantity calculated from posted stock movements',
            'variance' => 'stock balance quantity minus ledger quantity',
            'matched' => 'whether balance and ledger quantities agree',
        ];

        $rows = array_values(array_filter(
            $reconciliationService->details(),
            static fn (array $row): bool =>
                (! isset($filters['product_id'])
                    || (int) $row['product_id'] === (int) $filters['product_id'])
                && (! isset($filters['batch_id'])
                    || (int) $row['batch_id'] === (int) $filters['batch_id'])
                && (! isset($filters['location_id'])
                    || (int) $row['location_id'] === (int) $filters['location_id']),
        ));

        $filename = 'stock-reconciliation-'.now()->format('Ymd-His').'.csv';

        $auditService->recordActivity('csv_exported', null, [], [
            'resource' => 'stock_reconciliation',
            'row_count' => count($rows),
            'filters' => $filters,
            'filename' => $filename,
        ]);

        return response()->streamDownload(
            function () use ($rows, $timestamp, $columnDefinitions): void {
                $output = fopen('php://output', 'w');

                fputcsv(
                    $output,
                    [
                        'record_type',
                        'exported_at',
                        'column_definitions',
                        ...array_keys($columnDefinitions),
                    ],
                    ',',
                    '"',
                    ''
                );

                fputcsv($output, [
                    'metadata',
                    $timestamp,
                    implode('; ', array_map(
                        static fn (string $field, string $definition): string =>
                            "{$field}={$definition}",
                        array_keys($columnDefinitions),
                        array_values($columnDefinitions),
                    )),
                    ...array_fill(0, count($columnDefinitions), ''),
                ], ',', '"', '');

                foreach ($rows as $row) {
                    fputcsv($output, [
                        'stock-pair',
                        $timestamp,
                        '',
                        $this->safeCsvValue(
                            (string) ($row['sap_material_number'] ?? '')
                        ),
                        $this->safeCsvValue((string) $row['sku']),
                        $this->safeCsvValue((string) $row['product_name']),
                        $this->safeCsvValue((string) $row['batch_number']),
                        $this->safeCsvValue((string) $row['location_code']),
                        $row['balance'],
                        $row['ledger'],
                        $row['variance'],
                        $row['matched'] ? 'yes' : 'no',
                    ], ',', '"', '');
                }

                fclose($output);
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    private function authorizeReconciliationExport(): void
    {
        $user = request()->user();

        abort_unless(
            $user !== null && (
                $user->hasPermission(
                    'View and export all analytical reports and ERP exchanges'
                )
                || $user->hasPermission(
                    'View and export stock ledger, enquiry, and operational reports'
                )
                || $user->hasPermission(
                    'Export stock reconciliation (CSV)'
                )
            ),
            403
        );
    }

    /**
     * @param list<string> $fields
     * @param list<string> $headers
     * @return array<string, string>
     */
    private function suggestedMapping(array $fields, array $headers): array
    {
        $aliases = [
            'sap_material_number' => [
                'sap_material_number',
                'sap material number',
                'material_number',
                'material',
                'matnr',
            ],
            'sku' => ['sku', 'product_sku'],
            'name' => [
                'name',
                'product_name',
                'description_short',
                'material_description',
            ],
            'description' => ['description', 'long_description'],
            'category_id' => ['category_id'],
            'unit' => ['unit', 'base_unit', 'uom'],
            'min_level' => ['min_level', 'minimum_level'],
            'reorder_level' => ['reorder_level', 'reorder_point'],
            'max_level' => ['max_level', 'maximum_level'],
            'status' => ['status'],
        ];

        $mapping = [];

        foreach ($fields as $field) {
            foreach ($headers as $header) {
                if (in_array(
                    mb_strtolower(trim($header)),
                    $aliases[$field] ?? [],
                    true
                )) {
                    $mapping[$field] = $header;
                    break;
                }
            }
        }

        return $mapping;
    }

    private function safeCsvValue(string $value): string
    {
        return ! is_numeric($value)
            && preg_match('/^[\s]*[=+\-@]/', $value) === 1
                ? "'".$value
                : $value;
    }
}
