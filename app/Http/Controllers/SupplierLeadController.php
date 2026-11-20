<?php

namespace App\Http\Controllers;

use App\Models\SupplierLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use SplFileObject;

class SupplierLeadController extends Controller
{
    /**
     * Check whether the logged-in user can manage the Supplier Pool.
     *
     * Allowed:
     * - Administrator
     * - Warehouse Manager
     * - Procurement Officer
     *
     * Other roles are denied unless their permissions are explicitly
     * updated in config/roles.php.
     */
    private function checkAccess(Request $request): void
    {
        $user = $request->user();

        // Ensure the user is authenticated.
        abort_unless(
            $user,
            401,
            'You must log in to access the Supplier Pool.'
        );

        // Check the user's actual permissions.
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
                    'rejected'
                ])
            ],
            'source_type' => [
                'nullable',
                Rule::in([
                    'manual',
                    'website',
                    'facebook',
                    'linkedin',
                    'other'
                ])
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
                    'other'
                ])
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
                    'other'
                ])
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
                    'rejected'
                ])
            ],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $supplierLead->update($data);

        return to_route('supplier-pool.index')
            ->with('status', 'Supplier Pool record updated.');
    }

    /**
     * Import supplier leads from a CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
        $this->checkAccess($request);

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:5120'
            ],
        ]);

        $file = new SplFileObject(
            $request->file('file')->getRealPath(),
            'r'
        );

        $file->setCsvControl(',', '"', '');

        $headers = $file->fgetcsv();

        if (!$headers || $headers === [null]) {
            return back()->withErrors([
                'file' => 'The CSV file is empty.'
            ]);
        }

        $headers = array_map(
            fn ($value) => strtolower(trim((string) $value)),
            $headers
        );

        if (count($headers) !== count(array_unique($headers))) {
            return back()->withErrors([
                'file' => 'Your CSV contains duplicate column headings.'
            ]);
        }

        $required = ['company_name'];

        if (array_diff($required, $headers)) {
            return back()->withErrors([
                'file' => 'Your CSV must include a company_name column.'
            ]);
        }

        $count = 0;
        $userId = $request->user()->id;

        DB::transaction(function () use (
            $file,
            $headers,
            $userId,
            &$count
        ) {
            while (!$file->eof() && $count < 1000) {
                $values = $file->fgetcsv();

                if (!$values || $values === [null]) {
                    continue;
                }

                // Ignore rows containing more values than the header.
                if (count($values) > count($headers)) {
                    continue;
                }

                $values = array_pad(
                    $values,
                    count($headers),
                    null
                );

                $row = array_combine($headers, $values);

                if (!$row) {
                    continue;
                }

                $companyName = trim(
                    (string) ($row['company_name'] ?? '')
                );

                if ($companyName === '') {
                    continue;
                }

                SupplierLead::create([
                    'company_name' => $companyName,
                    'contact_person' => $row['contact_person'] ?? null,
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'website' => $row['website'] ?? null,
                    'source_type' => 'other',
                    'source_url' => $row['source_url'] ?? null,
                    'location' => $row['location'] ?? null,
                    'description' => $row['description'] ?? null,
                    'status' => 'discovered',
                    'added_by' => $userId,
                ]);

                $count++;
            }
        });

        return to_route('supplier-pool.index')
            ->with(
                'status',
                "{$count} potential supplier records imported."
            );
    }
}
