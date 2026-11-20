<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DispatchRequest;
use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\StockCount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WimsPagesRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_wims_pages_registry_contains_all_44_pages_with_valid_roles(): void
    {
        $config = config('wims_pages');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('pages', $config);
        $this->assertArrayHasKey('phase_2_plan', $config);

        $pages = $config['pages'];

        $this->assertCount(44, $pages);

        $allowedRoles = [
            'administrator',
            'warehouse_manager',
            'storekeeper',
            'procurement_officer',
            'dispatch_officer',
            'auditor',
        ];

        $pageIds = [];

        foreach ($pages as $index => $page) {
            $expectedId = sprintf('P%02d', $index + 1);

            $this->assertSame($expectedId, $page['id']);

            $pageIds[] = $page['id'];

            $this->assertIsInt($page['phase']);
            $this->assertGreaterThanOrEqual(1, $page['phase']);
            $this->assertLessThanOrEqual(13, $page['phase']);

            foreach ($page['roles'] as $role) {
                $this->assertContains(
                    $role,
                    $allowedRoles,
                    "Page {$page['id']} contains invalid role '{$role}'."
                );
            }
        }

        $this->assertCount(44, array_unique($pageIds));
    }

    public function test_phase_2_plan_contains_required_mappings_and_priorities(): void
    {
        $plan = config('wims_pages.phase_2_plan');

        $this->assertIsArray($plan);

        // Legacy to new role mapping.
        $this->assertSame([
            'admin' => 'administrator',
            'supervisor' => 'warehouse_manager',
            'dispatch_officer' => 'dispatch_officer',
            'user' => 'storekeeper',
        ], $plan['legacy_role_mapping']);

        // New roles.
        $this->assertSame(
            ['procurement_officer', 'auditor'],
            $plan['new_roles']
        );

        // Explicit page phase checks.
        $pages = collect(config('wims_pages.pages'))->keyBy('id');

        $this->assertSame(2, $pages['P02']['phase']);
        $this->assertSame(2, $pages['P04']['phase']);
        $this->assertSame(5, $pages['P22']['phase']);
        $this->assertSame(5, $pages['P23']['phase']);
        $this->assertSame(6, $pages['P28']['phase']);
        $this->assertSame(9, $pages['P36']['phase']);
        $this->assertSame(12, $pages['P44']['phase']);

        // Ordered priorities.
        $priorities = $plan['priorities'];

        $this->assertCount(6, $priorities);
        $this->assertStringStartsWith('(a)', $priorities[0]);
        $this->assertStringStartsWith('(b)', $priorities[1]);
        $this->assertStringStartsWith('(c)', $priorities[2]);
        $this->assertStringStartsWith('(d)', $priorities[3]);
        $this->assertStringStartsWith('(e)', $priorities[4]);
        $this->assertStringStartsWith('(f)', $priorities[5]);
    }

    public function test_audit_passes_with_zero_exposed_routes_and_zero_missing_auth(): void
    {
        $exitCode = Artisan::call('wims:audit');
        $output = Artisan::output();

        $this->assertSame(
            0,
            $exitCode,
            "wims:audit should exit 0 on clean security. Output:\n" . $output
        );

        $this->assertStringContainsString('AUDIT PASSED', $output);

        $this->assertMatchesRegularExpression(
            '/Confirmed Exposed Routes:\s+0/',
            $output
        );

        $this->assertMatchesRegularExpression(
            '/Routes Missing Auth MW:\s+0/',
            $output
        );
    }

    public function test_indeterminate_routes_with_real_records_redirect_guests_and_enforce_auth(): void
    {
        // Create real records to test parameterized routes.
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $supplier = Supplier::factory()->create();

        $location = Location::query()->create([
            'code' => 'WH-TEST-01',
            'name' => 'Test Location',
            'type' => 'warehouse',
            'status' => 'active',
        ]);

        $realRoutes = [
            route('categories.show', $category, false),
            route('categories.edit', $category, false),
            route('products.show', $product, false),
            route('products.edit', $product, false),
            route('suppliers.show', $supplier, false),
            route('suppliers.edit', $supplier, false),
            route('locations.show', $location, false),
            route('locations.edit', $location, false),
        ];

        // Guests must be redirected to login.
        foreach ($realRoutes as $uri) {
            $response = $this->get($uri);

            $response->assertRedirect(route('login'));
        }

        // Authenticated administrator must access real records.
        $admin = User::factory()->administrator()->create();

        foreach ($realRoutes as $uri) {
            $response = $this->actingAs($admin)->get($uri);

            $this->assertContains(
                $response->getStatusCode(),
                [200, 302],
                "Admin should access {$uri}"
            );
        }
    }

    /**
     * Create real records needed by parameterized catalogue routes.
     *
     * @return array{
     *     category: Category,
     *     product: Product,
     *     supplier: Supplier
     * }
     */
    private function createCatalogueTestRecords(): array
    {
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $supplier = Supplier::factory()->create();

        return [
            'category' => $category,
            'product' => $product,
            'supplier' => $supplier,
        ];
    }

    /**
     * Data provider supplying each of the 44 pages.
     *
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function pageProvider(): array
    {
        $config = require __DIR__ . '/../../config/wims_pages.php';

        $pages = $config['pages'] ?? [];

        $data = [];

        foreach ($pages as $page) {
            $data[$page['id'] . ': ' . $page['name']] = [
                $page['id'],
                $page,
            ];
        }

        return $data;
    }

    /**
     * Data-driven test for each page.
     *
     * Pages where phase <= completed_phase are tested for:
     * - Unauthenticated redirect to login (or 200 for guest pages).
     * - Allowed roles get 200 or 302, other roles get 403.
     * - Exactly one h1 and at least one main landmark.
     * - No stub text.
     * - Sidebar links do not point to missing pages.
     *
     * Pages not yet due are skipped with markTestSkipped.
     *
     * @param array<string, mixed> $page
     */
    #[DataProvider('pageProvider')]
    public function test_page_runtime_and_security_specifications(
        string $pageId,
        array $page
    ): void {
        $completedPhase = (int) config('wims.completed_phase', 2);

        // Pages not yet due are skipped, not removed.
        if ($page['phase'] > $completedPhase) {
            $this->markTestSkipped(
                "Page {$pageId} ({$page['name']}) is scheduled for Phase {$page['phase']} (current completed phase: {$completedPhase})."
            );
        }

        $isGuest = (bool) ($page['guest'] ?? false);
        $pageRoutes = $page['routes'] ?? [];
        $expectedRoles = $page['roles'] ?? [];

        // Special pages without direct routes.
        if ($pageId === 'P04') {
            foreach (['403', '404', '419', '500'] as $code) {
                $this->assertFileExists(
                    resource_path("views/errors/{$code}.blade.php")
                );
            }

            return;
        }

        if ($pageId === 'P44') {
            $offlineFile = public_path('offline.html');

            $this->assertFileExists($offlineFile);

            $content = file_get_contents($offlineFile);

            $this->assertIsString($content);

            $this->assertMatchesRegularExpression(
                '/(retry|reload|window\.location\.reload)/i',
                $content
            );

            return;
        }

        $this->assertNotEmpty(
            $pageRoutes,
            "Page {$pageId} must define at least one route."
        );

        $primaryRouteName = $pageRoutes[0];

        $this->assertTrue(
            Route::has($primaryRouteName),
            "Route '{$primaryRouteName}' is not registered."
        );

        $primaryRoute = Route::getRoutes()->getByName($primaryRouteName);

        $this->assertNotNull($primaryRoute);

        // Create real records for parameterized routes.
        $routeParameters = [];

        if (in_array($primaryRouteName, [
            'categories.show',
            'categories.edit',
            'products.show',
            'products.edit',
            'suppliers.show',
            'suppliers.edit',
        ], true)) {
            $records = $this->createCatalogueTestRecords();

            $routeParameters = match ($primaryRouteName) {
                'categories.show',
                'categories.edit' => [
                    'category' => $records['category'],
                ],

                'products.show',
                'products.edit' => [
                    'product' => $records['product'],
                ],

                'suppliers.show',
                'suppliers.edit' => [
                    'supplier' => $records['supplier'],
                ],

                default => [],
            };
        }

        // Create a real location for location detail and QR label routes.
        if (in_array($primaryRouteName, [
            'locations.show',
            'locations.label',
        ], true)) {
            $location = Location::query()->create([
                'name' => 'Test Warehouse',
                'code' => 'WH-TEST-01',
                'type' => 'warehouse',
                'status' => 'active',
            ]);

            $routeParameters['location'] = $location;
        }

        // Create a real purchase order for its detail route (P18).
        if ($primaryRouteName === 'purchase-orders.show') {
            $supplier = Supplier::factory()->create();

            $purchaseOrder = PurchaseOrder::query()->create([
                'order_number' => 'PO-REGISTRY-TEST',
                'supplier_id' => $supplier->id,
                'order_date' => now()->toDateString(),
                'expected_date' => now()->addDays(7)->toDateString(),
                'status' => PurchaseOrder::STATUS_DRAFT,
                'notes' => 'Registry test purchase order.',
            ]);

            $routeParameters['purchaseOrder'] = $purchaseOrder;
        }

        // Create a real goods receipt for its detail route (P21).
        if ($primaryRouteName === 'goods-receipts.show') {
            $receiver = User::factory()->create();

            $goodsReceipt = GoodsReceipt::query()->create([
                'receipt_number' => 'GRN-REGISTRY-TEST',
                'submission_token' => (string) Str::uuid(),
                'received_by' => $receiver->id,
                'status' => 'confirmed',
                'received_at' => now(),
                'confirmed_at' => now(),
                'notes' => 'Registry test goods receipt.',
            ]);

            $routeParameters['goodsReceipt'] = $goodsReceipt;
        }

        // Create real dispatch requests for FEFO picking and GDN detail routes.
        if (in_array($primaryRouteName, [
            'dispatch-requests.pick-list',
            'dispatch-requests.print',
        ], true)) {
            $records = $this->createCatalogueTestRecords();
            $requester = User::factory()->create();

            $isPrintPage = $primaryRouteName === 'dispatch-requests.print';

            $dispatchRequest = DispatchRequest::query()->create([
                'product_id' => $records['product']->id,
                'requested_quantity' => 1,
                'status' => $isPrintPage ? 'dispatched' : 'requested',
                'requested_by' => $requester->id,
                'dispatched_by' => $isPrintPage ? $requester->id : null,
                'gdn_number' => $isPrintPage ? 'GDN-REGISTRY-TEST' : null,
                'requested_at' => now(),
                'dispatched_at' => $isPrintPage ? now() : null,
            ]);

            $routeParameters['dispatchRequest'] = $dispatchRequest;
        }

        // Initialise stock count for static analysis.
        $stockCount = null;

        // Create real stock counts for cycle-count detail and approval routes.
        if (in_array($primaryRouteName, [
            'stock-counts.show',
            'stock-counts.review',
        ], true)) {
            $warehouse = Location::query()->create([
                'name' => 'Registry Count Warehouse',
                'type' => 'warehouse',
                'status' => 'active',
            ]);

            $zone = Location::query()->create([
                'parent_id' => $warehouse->id,
                'name' => 'Registry Count Zone',
                'type' => 'zone',
                'status' => 'active',
            ]);

            $rack = Location::query()->create([
                'parent_id' => $zone->id,
                'name' => 'Registry Count Rack',
                'type' => 'rack',
                'status' => 'active',
            ]);

            $bin = Location::query()->create([
                'parent_id' => $rack->id,
                'name' => 'Registry Count Bin',
                'code' => 'RC-' . uniqid(),
                'type' => 'bin',
                'status' => 'active',
            ]);

            $counter = User::factory()->create();

            $isReview = $primaryRouteName === 'stock-counts.review';

            $stockCount = StockCount::query()->create([
                'location_id' => $bin->id,
                'status' => $isReview ? 'submitted' : 'draft',
                'created_by' => $counter->id,
                'submitted_by' => $isReview ? $counter->id : null,
                'submitted_at' => $isReview ? now() : null,
            ]);

            $routeParameters['stockCount'] = $stockCount;
        }

        $testUri = route(
            $primaryRouteName,
            $routeParameters,
            false
        );

        // Use POST for the stock-count approval route.
        $requestPage = fn () => $primaryRouteName === 'stock-counts.review'
            ? $this->post($testUri, [
                'decision' => 'rejected',
                'reason' => 'Registry test review.',
            ])
            : $this->get($testUri);

        // Unauthenticated request check.
        if (! $isGuest) {
            $response = $requestPage();

            $response->assertRedirect(route('login'));
        } else {
            $response = $requestPage();

            $response->assertOk();
        }

        // Role-based access checks.
        if (! $isGuest && ! empty($expectedRoles)) {
            $allRoles = [
                'administrator',
                'warehouse_manager',
                'storekeeper',
                'procurement_officer',
                'dispatch_officer',
                'auditor',
            ];

            foreach ($allRoles as $role) {

                // Reset the count before testing each role.
                // The previous authorized user may have rejected it.
                if (
                    $primaryRouteName === 'stock-counts.review'
                    && $stockCount instanceof StockCount
                ) {
                    $stockCount->refresh()->forceFill([
                        'status' => 'submitted',
                        'reviewed_by' => null,
                        'review_note' => null,
                        'reviewed_at' => null,
                    ])->save();
                }

                $user = User::factory()->create([
                    'role' => $role,
                    'is_active' => true,
                ]);

                $response = $this->actingAs($user)->get($testUri);

                if ($primaryRouteName === 'stock-counts.review') {
                    $response = $this->actingAs($user)->post($testUri, [
                        'decision' => 'rejected',
                        'reason' => 'Registry test review.',
                    ]);
                }

                if (in_array($role, $expectedRoles, true)) {
                    $this->assertContains(
                        $response->getStatusCode(),
                        [200, 302],
                        "Role '{$role}' should have access to '{$primaryRouteName}'."
                    );
                } else {
                    $response->assertForbidden();
                }
            }
        }

        // Rendered DOM and stub checks.
        $testUser = User::factory()->create([
            'role' => $expectedRoles[0] ?? 'administrator',
            'is_active' => true,
        ]);

        // Restore submitted status before the rendered-page test.
        if (
            $primaryRouteName === 'stock-counts.review'
            && $stockCount instanceof StockCount
        ) {
            $stockCount->refresh()->forceFill([
                'status' => 'submitted',
                'reviewed_by' => null,
                'review_note' => null,
                'reviewed_at' => null,
            ])->save();
        }

        $testResponse = $isGuest
            ? $requestPage()
            : (
                $primaryRouteName === 'stock-counts.review'
                    ? $this->actingAs($testUser)->post($testUri, [
                        'decision' => 'rejected',
                        'reason' => 'Registry test review.',
                    ])
                    : $this->actingAs($testUser)->get($testUri)
            );

        if (
            $testResponse->getStatusCode() === 200
            && str_contains(
                strtolower((string) $testResponse->headers->get('Content-Type')),
                'text/html'
            )
        ) {
            $html = $testResponse->getContent();

            // Exactly one h1 element.
            preg_match_all('/<h1\b[^>]*>/i', $html, $h1Matches);

            $this->assertCount(
                1,
                $h1Matches[0],
                "Page {$pageId} must have exactly one <h1> element."
            );

            // Main landmark.
            $hasMainLandmark = preg_match(
                '/<main\b|role=["\']main["\']/i',
                $html
            );

            $this->assertSame(
                1,
                $hasMainLandmark,
                "Page {$pageId} must contain a <main> landmark."
            );

            // No stub text.
            $this->assertDoesNotMatchRegularExpression(
                '/\b(TODO|Lorem\s+ipsum|coming\s+soon|placeholder\s+page)\b/i',
                $html,
                "Page {$pageId} view contains stub text."
            );

            // Check internal links for missing pages.
            preg_match_all(
                "/<a\b[^>]*href=[\"']([^\"']+)[\"']/i",
                $html,
                $linkMatches
            );

            foreach ($linkMatches[1] as $href) {
                // Skip external links, non-page links, and template placeholders.
                if (
                    str_starts_with($href, '#')
                    || str_starts_with($href, 'mailto:')
                    || str_starts_with($href, 'tel:')
                    || str_starts_with($href, 'javascript:')
                    || str_contains($href, '${')
                    || str_contains($href, '{{')
                ) {
                    continue;
                }

                // Do not follow external domains.
                if (preg_match('/^https?:\/\//i', $href)) {
                    $host = parse_url($href, PHP_URL_HOST);

                    if ($host && ! in_array($host, [
                        'localhost',
                        '127.0.0.1',
                    ], true)) {
                        continue;
                    }
                }

                $linkPath = parse_url($href, PHP_URL_PATH);

                if (! $linkPath || $linkPath === '/') {
                    continue;
                }

                $linkResponse = $this->actingAs($testUser)->get($linkPath);

                $this->assertNotEquals(
                    404,
                    $linkResponse->getStatusCode(),
                    "Link '{$href}' on page {$pageId} points to a 404 missing page."
                );
            }
        }
    }
}
