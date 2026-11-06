<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
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

        // Legacy to new role mapping
        $this->assertSame([
            'admin' => 'administrator',
            'supervisor' => 'warehouse_manager',
            'dispatch_officer' => 'dispatch_officer',
            'user' => 'storekeeper',
        ], $plan['legacy_role_mapping']);

        // New roles
        $this->assertSame(['procurement_officer', 'auditor'], $plan['new_roles']);

        // Explicit page phase checks
        $pages = collect(config('wims_pages.pages'))->keyBy('id');
        $this->assertSame(2, $pages['P02']['phase']);
        $this->assertSame(2, $pages['P04']['phase']);
        $this->assertSame(5, $pages['P22']['phase']);
        $this->assertSame(5, $pages['P23']['phase']);
        $this->assertSame(6, $pages['P28']['phase']);
        $this->assertSame(9, $pages['P36']['phase']);
        $this->assertSame(12, $pages['P44']['phase']);

        // Ordered priorities
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

        $this->assertSame(0, $exitCode, "wims:audit should exit 0 on clean security. Output:\n".$output);
        $this->assertStringContainsString('AUDIT PASSED', $output);
        $this->assertMatchesRegularExpression('/Confirmed Exposed Routes:\s+0/', $output);
        $this->assertMatchesRegularExpression('/Routes Missing Auth MW:\s+0/', $output);
    }

    public function test_indeterminate_routes_with_real_records_redirect_guests_and_enforce_auth(): void
    {
        // Prove that parameterized routes previously indeterminate on fake IDs
        // redirect unauthenticated guests to login and correctly permit authenticated users.
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $supplier = Supplier::factory()->create();
        $location = Location::query()->create([
            'code' => 'WH-TEST-01',
            'name' => 'Test Location',
            'type' => 'warehouse',
            'is_active' => true,
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

        // 1. Unauthenticated requests to real records redirect to login
        foreach ($realRoutes as $uri) {
            $response = $this->get($uri);
            $response->assertRedirect(route('login'));
        }

        // 2. Authenticated administrator gets access to real records
        $admin = User::factory()->administrator()->create();
        foreach ($realRoutes as $uri) {
            $response = $this->actingAs($admin)->get($uri);
            $this->assertContains($response->getStatusCode(), [200, 302], "Admin should access {$uri}");
        }
    }

    /**
     * Data provider supplying each of the 44 pages.
     *
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function pageProvider(): array
    {
        $config = require __DIR__.'/../../config/wims_pages.php';
        $pages = $config['pages'] ?? [];

        $data = [];
        foreach ($pages as $page) {
            $data[$page['id'].': '.$page['name']] = [$page['id'], $page];
        }

        return $data;
    }

    /**
     * Data-driven test for each page:
     * Pages where phase <= completed_phase are tested for:
     * - Unauthenticated redirect to login (or 200 for guest pages)
     * - Allowed roles get 200, other roles get 403
     * - Exactly one <h1> and at least one <main> landmark
     * - No stub text (TODO, Lorem, coming soon, placeholder page)
     * - Sidebar links resolve and none points to a missing page
     *
     * Pages not yet due (phase > completed_phase) are skipped with markTestSkipped.
     *
     * @param  array<string, mixed>  $page
     */
    #[DataProvider('pageProvider')]
    public function test_page_runtime_and_security_specifications(string $pageId, array $page): void
    {
        $completedPhase = (int) (env('WIMS_PHASE') ?? config('wims.completed_phase', 2));

        // Pages not yet due are skipped, not removed
        if ($page['phase'] > $completedPhase) {
            $this->markTestSkipped(
                "Page {$pageId} ({$page['name']}) is scheduled for Phase {$page['phase']} (current completed phase: {$completedPhase})."
            );
        }

        $isGuest = (bool) ($page['guest'] ?? false);
        $pageRoutes = $page['routes'] ?? [];
        $expectedRoles = $page['roles'] ?? [];

        // Special pages without direct routes (P04 error pages, P44 offline page)
        if ($pageId === 'P04') {
            foreach (['403', '404', '419', '500'] as $code) {
                $this->assertFileExists(resource_path("views/errors/{$code}.blade.php"));
            }

            return;
        }

        if ($pageId === 'P44') {
            $offlineFile = public_path('offline.html');
            $this->assertFileExists($offlineFile);
            $content = file_get_contents($offlineFile);
            $this->assertMatchesRegularExpression('/(retry|reload|window\.location\.reload)/i', $content);

            return;
        }

        $this->assertNotEmpty($pageRoutes, "Page {$pageId} must define at least one route.");
        $primaryRouteName = $pageRoutes[0];
        $this->assertTrue(Route::has($primaryRouteName), "Route '{$primaryRouteName}' is not registered.");

        $primaryRoute = Route::getRoutes()->getByName($primaryRouteName);
        $uri = $primaryRoute->uri();

        // 1. Unauthenticated request check
        if (! $isGuest) {
            $response = $this->get('/'.ltrim(preg_replace('/\{[^}]+\}/', '999999', $uri), '/'));
            $response->assertRedirect(route('login'));
        } else {
            $response = $this->get('/'.ltrim(preg_replace('/\{[^}]+\}/', '999999', $uri), '/'));
            $response->assertOk();
        }

        // 2. Role-based access checks (for non-guest routes)
        if (! $isGuest && ! empty($expectedRoles)) {
            $allRoles = ['administrator', 'warehouse_manager', 'storekeeper', 'procurement_officer', 'dispatch_officer', 'auditor'];

            foreach ($allRoles as $role) {
                $user = User::factory()->make(['role' => $role, 'active' => true]);

                $response = $this->actingAs($user)->get('/'.ltrim(preg_replace('/\{[^}]+\}/', '999999', $uri), '/'));

                if (in_array($role, $expectedRoles, true)) {
                    // Allowed roles get 200 (or redirect if resource requires parameters)
                    $this->assertContains($response->getStatusCode(), [200, 302], "Role '{$role}' should have access to '{$primaryRouteName}'.");
                } else {
                    // Disallowed roles get 403 Forbidden
                    $response->assertForbidden();
                }
            }
        }

        // 3. Rendered DOM and Stub checks on accessible response
        $testUser = User::factory()->make(['role' => $expectedRoles[0] ?? 'administrator', 'active' => true]);
        $testResponse = $isGuest
            ? $this->get('/'.ltrim(preg_replace('/\{[^}]+\}/', '999999', $uri), '/'))
            : $this->actingAs($testUser)->get('/'.ltrim(preg_replace('/\{[^}]+\}/', '999999', $uri), '/'));

        if ($testResponse->getStatusCode() === 200) {
            $html = $testResponse->getContent();

            // One <h1> tag
            preg_match_all('/<h1\b[^>]*>/i', $html, $h1Matches);
            $this->assertCount(1, $h1Matches[0], "Page {$pageId} must have exactly one <h1> element.");

            // Main landmark
            $hasMainLandmark = preg_match('/<main\b|role=["\']main["\']/i', $html);
            $this->assertSame(1, $hasMainLandmark, "Page {$pageId} must contain a <main> landmark.");

            // No stub text
            $this->assertDoesNotMatchRegularExpression(
                '/\b(TODO|Lorem\s+ipsum|coming\s+soon|placeholder\s+page)\b/i',
                $html,
                "Page {$pageId} view contains stub text."
            );

            // Sidebar links resolve and none points to a missing page
            preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\']/i', $html, $linkMatches);
            foreach ($linkMatches[1] as $href) {
                if (str_starts_with($href, 'http://localhost') || str_starts_with($href, '/')) {
                    $linkPath = parse_url($href, PHP_URL_PATH);
                    if ($linkPath && $linkPath !== '/' && $linkPath !== '#') {
                        $linkResp = $this->actingAs($testUser)->get($linkPath);
                        $this->assertNotEquals(404, $linkResp->getStatusCode(), "Link '{$href}' on page {$pageId} points to a 404 missing page.");
                    }
                }
            }
        }
    }
}
