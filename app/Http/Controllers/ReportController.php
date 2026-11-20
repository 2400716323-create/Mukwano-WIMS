<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Location;
use App\Models\Product;
use App\Services\AuditService;
use App\Services\ReportPdfService;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportService $reportService): View
    {
        $this->authorizeReports();

        return view('reports.index', [
            'reports' => $reportService->availableReports(),
        ]);
    }

    public function show(
        Request $request,
        string $report,
        ReportService $reportService
    ): View {
        $this->authorizeReports();
        $this->ensureValidReport($report, $reportService);
        $filters = $this->filters($request);

        return view('reports.show', [
            'report' => $reportService->report($report, $filters),
            'reports' => $reportService->availableReports(),
            'filters' => $filters,
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name']),
            'batches' => Batch::query()->with('product')->orderBy('batch_number')->get(),
            'locations' => Location::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function csv(
        Request $request,
        string $report,
        ReportService $reportService,
        AuditService $auditService,
    ): StreamedResponse {
        $this->authorizeReports();
        $this->ensureValidReport($report, $reportService);
        $filters = $this->filters($request);
        $data = $reportService->report($report, $filters);
        $filename = $report.'-'.now()->format('Ymd-His').'.csv';

        $auditService->recordActivity('csv_exported', null, [], [
            'resource' => $report,
            'row_count' => $data['totals']['rows'],
            'filters' => $filters,
            'filename' => $filename,
        ]);

        return response()->streamDownload(function () use ($data): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, array_values($data['columns']), ',', '"', '');

            foreach ($data['rows'] as $row) {
                fputcsv($output, array_map(
                    fn (string $key): string => $this->safeCsvValue((string) ($row[$key] ?? '')),
                    array_keys($data['columns']),
                ), ',', '"', '');
            }

            $totalRow = array_fill_keys(array_keys($data['columns']), '');
            $firstColumn = array_key_first($data['columns']);

            if ($firstColumn !== null) {
                $totalRow[$firstColumn] = 'TOTAL ('.$data['totals']['rows'].' rows)';
            }

            foreach ([
                'quantity' => 'quantity',
                'on_hand' => 'quantity',
                'ordered_quantity' => 'ordered_quantity',
                'received_quantity' => 'received_quantity',
                'system_quantity' => 'system_quantity',
                'physical_quantity' => 'physical_quantity',
                'balance_quantity' => 'balance_quantity',
                'ledger_quantity' => 'ledger_quantity',
                'variance' => 'variance',
            ] as $column => $total) {
                if (isset($data['columns'][$column], $data['totals'][$total])) {
                    $totalRow[$column] = $data['totals'][$total];
                }
            }

            if (isset($data['columns']['matched'], $data['totals']['matched'])) {
                $totalRow['matched'] = $data['totals']['matched'].' matched; '
                    .$data['totals']['mismatched'].' mismatched';
            }

            fputcsv($output, array_values($totalRow), ',', '"', '');
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(
        Request $request,
        string $report,
        ReportService $reportService,
        ReportPdfService $pdfService,
    ): StreamedResponse {
        $this->authorizeReports();
        $this->ensureValidReport($report, $reportService);
        $filters = $this->filters($request);
        $data = $reportService->report($report, $filters);
        $filename = $report.'-'.now()->format('Ymd-His').'.pdf';
        $pdf = $pdfService->render($data, $filters);

        return response()->streamDownload(
            static fn () => print $pdf,
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function authorizeReports(): void
    {
        $user = request()->user();

        abort_unless(
            $user !== null && (
                $user->hasPermission('View and export all analytical reports and ERP exchanges')
                || $user->hasPermission('View and export stock ledger, enquiry, and operational reports')
                || $user->hasPermission('Export analytical reports (PDF, CSV)')
            ),
            403
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'approved',
                    'partially_received',
                    'received',
                    'cancelled',
                    'requested',
                    'dispatched',
                    'submitted',
                    'rejected',
                ]),
            ],
        ]);
    }

    private function ensureValidReport(string $report, ReportService $reportService): void
    {
        Validator::make(['report' => $report], [
            'report' => ['required', Rule::in(array_keys($reportService->availableReports()))],
        ])->validate();
    }

    private function safeCsvValue(string $value): string
    {
        return ! is_numeric($value) && preg_match('/^[\s]*[=+\-@]/', $value) === 1
            ? "'".$value
            : $value;
    }
}
