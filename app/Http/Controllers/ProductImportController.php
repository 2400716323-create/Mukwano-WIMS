<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportProductsRequest;
use App\Models\Product;
use App\Services\AuditService;
use App\Services\ErpProductImportService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportController extends Controller
{
    private const CSV_COLUMNS = [
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
    ];

    public function export(AuditService $auditService): StreamedResponse
    {
        $this->authorize('viewAny', Product::class);
        $filename = 'products-'.now()->format('Ymd-His').'.csv';
        $auditService->recordActivity('csv_exported', null, [], [
            'resource' => 'products',
            'row_count' => Product::query()->count(),
            'filename' => $filename,
        ]);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, self::CSV_COLUMNS, ',', '"', '');

            Product::query()->orderBy('id')->chunk(500, function ($products) use ($output): void {
                foreach ($products as $product) {
                    $values = $product->only(self::CSV_COLUMNS);
                    $values = array_map(fn ($value) => $this->escapeSpreadsheetFormula((string) ($value ?? '')), $values);
                    fputcsv($output, $values, ',', '"', '');
                }
            });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(
        ImportProductsRequest $request,
        ErpProductImportService $importService,
    ): View {
        $staged = $importService->stage($request->file('file'), (int) $request->user()->id);
        $fields = $importService->fields();

        return view('erp-exchange.mapping', [
            ...$staged,
            'fields' => $fields,
            'suggested_mapping' => $this->suggestedMapping($fields, $staged['headers']),
        ]);
    }

    /**
     * @param  list<string>  $fields
     * @param  list<string>  $headers
     * @return array<string, string>
     */
    private function suggestedMapping(array $fields, array $headers): array
    {
        $mapping = [];
        foreach ($fields as $field) {
            foreach ($headers as $header) {
                if (mb_strtolower($header) === $field) {
                    $mapping[$field] = $header;
                    break;
                }
            }
        }

        return $mapping;
    }

    private function escapeSpreadsheetFormula(string $value): string
    {
        return preg_match('/^[\s]*[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
