<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use SplFileObject;

class ErpProductImportService
{
    /**
     * @return list<string>
     */
    public function fields(): array
    {
        return [
            'sap_material_number',
            'sku',
            'name',
            'description',
            'category_id',
            'unit',
            'min_level',
            'reorder_level',
            'max_level',
            'status',
        ];
    }

    /**
     * @return array{import_id: string, headers: list<string>, sample_rows: list<list<string>>}
     */
    public function stage(UploadedFile $file, int $userId): array
    {
        $contents = file_get_contents($file->getRealPath());
        if ($contents === false || ! mb_check_encoding($contents, 'UTF-8')) {
            throw ValidationException::withMessages([
                'file' => 'The CSV must be encoded as UTF-8.',
            ]);
        }

        $csv = new SplFileObject($file->getRealPath(), 'r');
        $csv->setCsvControl(',', '"', '');
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $headers = $csv->fgetcsv();
        if ($headers === false || $headers === [null]) {
            throw ValidationException::withMessages(['file' => 'The CSV file is empty.']);
        }

        $headers = array_map(
            static fn ($header): string => trim((string) $header, "\xEF\xBB\xBF \t\n\r\0\x0B"),
            $headers,
        );
        if (count($headers) > 50
            || in_array('', $headers, true)
            || array_filter($headers, static fn (string $header): bool => mb_strlen($header) > 100) !== []) {
            throw ValidationException::withMessages([
                'file' => 'The CSV must have between 1 and 50 non-empty column headers of at most 100 characters.',
            ]);
        }
        $normalizedHeaders = array_map(static fn (string $header): string => mb_strtolower($header), $headers);
        if (count($normalizedHeaders) !== count(array_unique($normalizedHeaders))) {
            throw ValidationException::withMessages(['file' => 'The CSV contains duplicate column names.']);
        }

        $rows = [];
        while (! $csv->eof()) {
            $values = $csv->fgetcsv();
            if ($values === false || $values === [null]) {
                continue;
            }
            if (count($values) !== count($headers)) {
                throw ValidationException::withMessages([
                    'file' => 'Every CSV row must have the same number of columns as the header.',
                ]);
            }
            $rows[] = array_map(static fn ($value): string => trim((string) $value), $values);
            if (count($rows) > 1000) {
                throw ValidationException::withMessages([
                    'file' => 'A product-master import cannot contain more than 1,000 data rows.',
                ]);
            }
        }
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'The CSV file has no product rows.']);
        }

        $importId = (string) Str::uuid();
        $this->save($importId, [
            'user_id' => $userId,
            'created_at' => now()->toIso8601String(),
            'status' => 'mapping',
            'headers' => $headers,
            'rows' => $rows,
        ]);

        return [
            'import_id' => $importId,
            'headers' => $headers,
            'sample_rows' => array_slice($rows, 0, 10),
        ];
    }

    /**
     * @param  array<string, string|null>  $mapping
     * @return array<string, mixed>
     */
    public function validateMapping(string $importId, int $userId, array $mapping): array
    {
        $staged = $this->load($importId, $userId);
        abort_unless($staged['status'] === 'mapping', 409, 'This import is no longer awaiting column mapping.');
        $allowedHeaders = $staged['headers'];
        $mapping = array_intersect_key($mapping, array_flip($this->fields()));

        foreach ($mapping as $field => $header) {
            if ($header !== null && $header !== '' && ! in_array($header, $allowedHeaders, true)) {
                throw ValidationException::withMessages(["mapping.{$field}" => 'Select a column from the uploaded file.']);
            }
        }
        foreach (['sap_material_number', 'name'] as $requiredField) {
            if (empty($mapping[$requiredField])) {
                throw ValidationException::withMessages([
                    "mapping.{$requiredField}" => 'Map this required product field to a CSV column.',
                ]);
            }
        }
        $selectedHeaders = array_filter($mapping, static fn (?string $header): bool => $header !== null && $header !== '');
        if (count($selectedHeaders) !== count(array_unique($selectedHeaders))) {
            throw ValidationException::withMessages(['mapping' => 'Each CSV column can only be mapped once.']);
        }

        $headerIndexes = array_flip($allowedHeaders);
        $rows = [];
        $seenSapNumbers = [];
        $seenSkus = [];
        foreach ($staged['rows'] as $index => $sourceValues) {
            $data = [];
            foreach ($mapping as $field => $header) {
                if ($header !== null && $header !== '') {
                    $data[$field] = trim($sourceValues[$headerIndexes[$header]]);
                }
            }

            $sapNumber = trim((string) ($data['sap_material_number'] ?? ''));
            $existing = $sapNumber === ''
                ? null
                : Product::query()->where('sap_material_number', $sapNumber)->first();
            $data = $this->completeProductData($data, $existing);
            $errors = [];

            $sapKey = mb_strtolower($sapNumber);
            if ($sapKey !== '') {
                $seenSapNumbers[$sapKey][] = $index;
            }
            $skuKey = mb_strtolower((string) ($data['sku'] ?? ''));
            if ($skuKey !== '') {
                $seenSkus[$skuKey][] = $index;
            }

            $validator = Validator::make($data, [
                'sap_material_number' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('products', 'sap_material_number')->ignore($existing?->id),
                ],
                'sku' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('products', 'sku')->ignore($existing?->id),
                ],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:5000'],
                'category_id' => [
                    'required',
                    'integer',
                    Rule::exists('categories', 'id')->where('status', 'active'),
                ],
                'unit' => ['required', 'string', 'max:32'],
                'min_level' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
                'reorder_level' => [
                    'required',
                    'numeric',
                    'min:0',
                    'decimal:0,3',
                    'gte:min_level',
                    'lte:max_level',
                ],
                'max_level' => ['required', 'numeric', 'min:0', 'decimal:0,3', 'gte:reorder_level'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            if ($validator->fails()) {
                $errors = $validator->errors()->all();
            }

            $rows[] = [
                'row_number' => $index + 2,
                'source' => $sourceValues,
                'data' => $data,
                'existing_product_id' => $existing?->id,
                'errors' => $errors,
            ];
        }

        foreach ([
            'SAP material number' => $seenSapNumbers,
            'SKU' => $seenSkus,
        ] as $field => $duplicates) {
            foreach ($duplicates as $indexes) {
                if (count($indexes) < 2) {
                    continue;
                }
                foreach ($indexes as $index) {
                    $rows[$index]['errors'][] = "Duplicate {$field} in this CSV file.";
                }
            }
        }

        $staged['status'] = 'validated';
        $staged['mapping'] = $mapping;
        $staged['validated_rows'] = $rows;
        $this->save($importId, $staged);

        return $staged;
    }

    /**
     * @param  array<string, mixed>  $staged
     * @return array{created: int, updated: int, rejected: int}
     */
    public function confirm(string $importId, int $userId, AuditService $auditService): array
    {
        $staged = $this->load($importId, $userId);
        abort_unless($staged['status'] === 'validated', 409, 'Validate the column mapping before confirming this import.');
        $validRows = array_values(array_filter(
            $staged['validated_rows'],
            static fn (array $row): bool => $row['errors'] === [],
        ));
        $created = 0;
        $updated = 0;
        $rejected = count($staged['validated_rows']) - count($validRows);

        DB::transaction(function () use ($validRows, $auditService, $userId, $rejected, &$created, &$updated): void {
            foreach ($validRows as $row) {
                $product = $row['existing_product_id'] === null
                    ? Product::query()->create($row['data'])
                    : Product::query()->findOrFail($row['existing_product_id']);

                if ($row['existing_product_id'] === null) {
                    $auditService->record($product, 'imported', [], $product->only($this->fields()));
                    $created++;
                } else {
                    Gate::authorize('update', $product);
                    $oldValues = $product->only($this->fields());
                    $product->fill($row['data'])->save();
                    $auditService->record($product, 'updated_from_erp', $oldValues, $product->only($this->fields()));
                    $updated++;
                }
            }
            $auditService->recordActivity('csv_imported', null, [], [
                'created' => $created,
                'updated' => $updated,
                'rejected' => $rejected,
            ], $userId);
        });

        foreach ($staged['validated_rows'] as &$row) {
            $row['result'] = $row['errors'] === []
                ? ($row['existing_product_id'] === null ? 'created' : 'updated')
                : 'rejected';
        }
        unset($row);

        $summary = [
            'created' => $created,
            'updated' => $updated,
            'rejected' => $rejected,
        ];
        $staged['status'] = 'confirmed';
        $staged['summary'] = $summary;
        $this->save($importId, $staged);

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $importId, int $userId): array
    {
        return $this->load($importId, $userId);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function completeProductData(array $data, ?Product $existing): array
    {
        $defaults = $existing?->only($this->fields()) ?? [
            'sku' => $data['sap_material_number'] ?? null,
            'name' => $data['name'] ?? null,
            'description' => null,
            'category_id' => null,
            'unit' => null,
            'min_level' => '0.000',
            'reorder_level' => '0.000',
            'max_level' => '0.000',
            'status' => 'active',
        ];
        foreach ($data as $field => $value) {
            if ($value !== '') {
                $defaults[$field] = $value;
            } elseif ($field === 'description') {
                $defaults[$field] = null;
            }
        }
        $defaults['sap_material_number'] = trim((string) ($data['sap_material_number'] ?? ''));
        if (empty($defaults['sku'])) {
            $defaults['sku'] = $defaults['sap_material_number'];
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>  $staged
     */
    private function save(string $importId, array $staged): void
    {
        $stored = Storage::disk('local')->put(
            "erp-imports/{$importId}.json",
            json_encode($staged, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
        if (! $stored) {
            throw new RuntimeException('Unable to store the staged ERP import.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function load(string $importId, int $userId): array
    {
        abort_unless(Str::isUuid($importId), 404);
        $path = "erp-imports/{$importId}.json";
        abort_unless(Storage::disk('local')->exists($path), 404);
        $staged = json_decode(Storage::disk('local')->get($path), true, flags: JSON_THROW_ON_ERROR);
        abort_unless((int) ($staged['user_id'] ?? 0) === $userId, 404);
        abort_unless(now()->diffInHours($staged['created_at']) < 24, 410, 'This staged import has expired.');

        return $staged;
    }
}
