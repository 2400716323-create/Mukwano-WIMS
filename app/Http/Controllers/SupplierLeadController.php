<?php

namespace App\Http\Controllers;

use App\Models\SupplierLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SupplierLeadController extends Controller
{
    /**
     * Check whether the logged-in user can manage the Supplier Pool.
     *
     * Allowed:
     * - Administrator
     * - Warehouse Manager
     * - Procurement Officer
     */
    private function checkAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user,
            401,
            'You must log in to access the Supplier Pool.'
        );

        $canManageSuppliers =
            $user->hasPermission('Manage supplier records and contacts')
            || $user->hasPermission('Manage product catalog, categories, and suppliers');

        abort_unless(
            $canManageSuppliers,
            403,
            'You do not have permission to access the Supplier Pool.'
        );
    }

    /**
     * Display supplier leads.
     */
    public function index(Request $request): View
    {
        $this->checkAccess($request);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                Rule::in([
                    'discovered',
                    'under_review',
                    'verified',
                    'rejected',
                ]),
            ],
            'source_type' => [
                'nullable',
                Rule::in([
                    'manual',
                    'website',
                    'facebook',
                    'linkedin',
                    'other',
                ]),
            ],
        ]);

        $leads = SupplierLead::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $filters['source_type'] ?? null,
                fn ($query, $source) => $query->where('source_type', $source)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total' => SupplierLead::count(),
            'discovered' => SupplierLead::where('status', 'discovered')->count(),
            'under_review' => SupplierLead::where('status', 'under_review')->count(),
            'verified' => SupplierLead::where('status', 'verified')->count(),
        ];

        return view('supplier-pool.index', compact(
            'leads',
            'filters',
            'metrics'
        ));
    }

    /**
     * Display the form for adding a supplier.
     */
    public function create(Request $request): View
    {
        $this->checkAccess($request);

        return view('supplier-pool.form', [
            'lead' => new SupplierLead(),
        ]);
    }

    /**
     * Save a new supplier lead.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->checkAccess($request);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:2048'],
            'source_type' => [
                'required',
                Rule::in([
                    'manual',
                    'website',
                    'facebook',
                    'linkedin',
                    'other',
                ]),
            ],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $data['added_by'] = $request->user()->id;
        $data['status'] = 'discovered';

        SupplierLead::create($data);

        return to_route('supplier-pool.index')
            ->with('status', 'Potential supplier added to the Supplier Pool.');
    }

    /**
     * Display the supplier editing form.
     */
    public function edit(
        Request $request,
        SupplierLead $supplierLead
    ): View {
        $this->checkAccess($request);

        return view('supplier-pool.form', [
            'lead' => $supplierLead,
        ]);
    }

    /**
     * Update an existing supplier lead.
     */
    public function update(
        Request $request,
        SupplierLead $supplierLead
    ): RedirectResponse {
        $this->checkAccess($request);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:2048'],
            'source_type' => [
                'required',
                Rule::in([
                    'manual',
                    'website',
                    'facebook',
                    'linkedin',
                    'other',
                ]),
            ],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => [
                'required',
                Rule::in([
                    'discovered',
                    'under_review',
                    'verified',
                    'rejected',
                ]),
            ],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $supplierLead->update($data);

        return to_route('supplier-pool.index')
            ->with('status', 'Supplier Pool record updated.');
    }

    /**
     * Download the Mukwano supplier reference workbook.
     */
    public function downloadWorkbook(
        Request $request
    ): BinaryFileResponse {
        $this->checkAccess($request);

        $path = public_path(
            'downloads/Mukwano_250_Supplier_Pool.xlsx'
        );

        abort_unless(
            file_exists($path) && is_file($path),
            404,
            'The supplier workbook could not be found.'
        );

        return response()->download(
            $path,
            'Mukwano_250_Supplier_Pool.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'private, no-store',
            ]
        );
    }

    /**
     * Import supplier leads from an Excel or CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
        $this->checkAccess($request);

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,csv,txt',
                'max:5120',
            ],
        ]);

        try {
            $path = $request->file('file')->getRealPath();

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile(
                $path
            );

            if ($reader instanceof \PhpOffice\PhpSpreadsheet\Reader\Csv) {
                $reader->setDelimiter(',');
                $reader->setEnclosure('"');
            }

            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($path);

            $sheet = $spreadsheet->getSheetByName('Supplier Master List')
                ?? $spreadsheet->getActiveSheet();

            $rows = $sheet->toArray('', true, true, false);

            if (count($rows) < 2) {
                return back()->withErrors([
                    'file' => 'The selected file contains no supplier records.',
                ]);
            }

            $headers = array_map(function ($value) {
                $value = preg_replace(
                    '/^\xEF\xBB\xBF/',
                    '',
                    (string) $value
                );

                return strtolower(trim($value));
            }, array_shift($rows));

            if (count($headers) !== count(array_unique($headers))) {
                return back()->withErrors([
                    'file' => 'The file contains duplicate column headings.',
                ]);
            }

            $normalise = fn ($value) => preg_replace(
                '/[^a-z0-9]+/',
                '_',
                trim(strtolower((string) $value))
            );

            $headers = array_map($normalise, $headers);

            $columns = [
                'supplier_company_name' => 'company_name',
                'supplier_name' => 'company_name',
                'company_name' => 'company_name',
                'contact_person' => 'contact_person',
                'email_address' => 'email',
                'email' => 'email',
                'phone_number' => 'phone',
                'phone' => 'phone',
                'website' => 'website',
                'source_url' => 'source_url',
                'district_location' => 'district',
                'district' => 'district',
                'location' => 'district',
                'physical_address' => 'physical_address',
                'product_description_scope' => 'description',
                'products_supplied' => 'products',
                'supplier_category' => 'category',
                'source_verification_notes' => 'notes',
            ];

            $mapped = array_map(
                fn ($header) => $columns[$header] ?? $header,
                $headers
            );

            if (!in_array('company_name', $mapped, true)) {
                return back()->withErrors([
                    'file' => 'The file must contain a Supplier / Company Name or company_name column.',
                ]);
            }

            $existingNames = SupplierLead::pluck('company_name')
                ->map(fn ($name) => strtolower(trim($name)))
                ->flip()
                ->all();

            $count = 0;
            $duplicates = 0;
            $skipped = 0;

            $userId = $request->user()->id;

            DB::transaction(function () use (
                $rows,
                $mapped,
                &$existingNames,
                $userId,
                &$count,
                &$duplicates,
                &$skipped
            ) {
                foreach ($rows as $values) {
                    if (count($values) !== count($mapped)) {
                        $skipped++;
                        continue;
                    }

                    $row = array_combine($mapped, $values);

                    $companyName = trim(
                        (string) ($row['company_name'] ?? '')
                    );

                    if ($companyName === '') {
                        $skipped++;
                        continue;
                    }

                    $key = strtolower($companyName);

                    if (isset($existingNames[$key])) {
                        $duplicates++;
                        continue;
                    }

                    $existingNames[$key] = true;

                    $physical = trim(
                        (string) ($row['physical_address'] ?? '')
                    );

                    $district = trim(
                        (string) ($row['district'] ?? '')
                    );

                    $location = implode(', ', array_filter([
                        $physical,
                        $district,
                    ]));

                    $description = implode("\n", array_filter([
                        $row['category'] ?? '',
                        $row['products'] ?? '',
                        $row['description'] ?? '',
                        $row['notes'] ?? '',
                    ]));

                    $sourceUrl = trim(
                        (string) ($row['source_url'] ?? '')
                    );

                    $website = trim(
                        (string) ($row['website'] ?? '')
                    );

                    if (
                        $website === ''
                        && filter_var($sourceUrl, FILTER_VALIDATE_URL)
                    ) {
                        $website = $sourceUrl;
                    }

                    SupplierLead::create([
                        'company_name' => $companyName,
                        'contact_person' => $row['contact_person'] ?? null,
                        'email' => $row['email'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'website' => $website ?: null,
                        'source_type' => 'other',
                        'source_url' => $sourceUrl ?: null,
                        'location' => $location ?: null,
                        'description' => $description ?: null,
                        'status' => 'discovered',
                        'added_by' => $userId,
                    ]);

                    $count++;
                }
            });

            $spreadsheet->disconnectWorksheets();

            return to_route('supplier-pool.index')->with(
                'status',
                "{$count} supplier records imported. {$duplicates} duplicates skipped and {$skipped} invalid or empty rows skipped."
            );

        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'file' => 'The supplier file could not be imported. Please check its format and headings.',
            ]);
        }
    }
}
