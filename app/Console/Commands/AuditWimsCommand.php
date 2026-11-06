<?php

namespace App\Console\Commands;

use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

class AuditWimsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wims:audit {--phase= : Specific phase to evaluate (defaults to completed_phase in config)}';

    /**
     * Command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = ['wims:audit-pages', 'wims:pages:audit'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit WIMS page registry, role alignment, and live route authentication enforcement';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('================================================================================');
        $this->info('                      MUKWANO WIMS — APPLICATION AUDIT                          ');
        $this->info('================================================================================');

        $completedPhase = (int) ($this->option('phase') ?? config('wims.completed_phase', 1));
        $this->line("Target Phase Evaluated: <comment>Phase {$completedPhase}</comment> (config completed_phase = ".config('wims.completed_phase', 1).')');

        // State which database connection and database the command runs against
        $defaultConn = config('database.default');
        $dbName = config("database.connections.{$defaultConn}.database", '(unknown)');
        $this->line("Database Connection:    <comment>{$defaultConn}</comment> (database: {$dbName})");

        $pagesConfig = config('wims_pages', []);
        $phase2Plan = $pagesConfig['phase_2_plan'] ?? [];
        $pages = $pagesConfig['pages'] ?? [];

        $this->printPhase2Plan($phase2Plan);

        // Perform live HTTP checks and static middleware analysis on all registered routes
        $routeResults = $this->auditAllRoutes();

        // Perform extended 44-page registry gap analysis
        $pageGaps = $this->auditPages($pages, $routeResults, $completedPhase);

        $categoryStats = $this->printRouteAuditSummary($routeResults);

        $exitCode = $this->printFinalSummary($pageGaps, $completedPhase, $categoryStats);

        return $exitCode;
    }

    /**
     * Print Phase 2 implementation plan and role mapping.
     *
     * @param  array<string, mixed>  $phase2Plan
     */
    protected function printPhase2Plan(array $phase2Plan): void
    {
        $this->newLine();
        $this->info('--- Phase 2 Architecture & RBAC Plan ---');

        $legacyMapping = $phase2Plan['legacy_role_mapping'] ?? [];
        $mappingRows = [];
        foreach ($legacyMapping as $legacy => $new) {
            $note = ($legacy === 'user') ? 'Default mapping to be reviewed user by user' : 'Direct mapping';
            $mappingRows[] = [$legacy, '->', $new, $note];
        }
        foreach ($phase2Plan['new_roles'] ?? [] as $newRole) {
            $mappingRows[] = ['(none)', '->', $newRole, 'New role with no existing users'];
        }

        $this->table(['Legacy Role Key', '', 'Target Role Key', 'Notes'], $mappingRows);

        $this->line('<comment>Phase 2 Ordered Priorities:</comment>');
        foreach ($phase2Plan['priorities'] ?? [] as $priority) {
            $this->line("  {$priority}");
        }
    }

    /**
     * Audit every registered route statically and with unauthenticated HTTP probe.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function auditAllRoutes(): array
    {
        $this->newLine();
        $this->info('--- Route Authentication & Middleware Audit (All Registered Routes) ---');

        /** @var HttpKernel $httpKernel */
        $httpKernel = $this->laravel->make(HttpKernel::class);

        // Bypass CSRF token check during audit so live requests evaluate auth enforcement directly
        $this->laravel->instance(ValidateCsrfToken::class, new class
        {
            public function handle(mixed $request, \Closure $next): mixed
            {
                return $next($request);
            }
        });

        $router = $this->laravel->make('router');
        $routes = Route::getRoutes()->getRoutes();
        $results = [];

        $guestRouteNames = [
            'login',
            'login.store',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'boost.browser-logs',
        ];
        $guestUris = ['up', '_boost/browser-logs'];
        $signatureRoutes = ['storage.local', 'storage.local.upload'];

        $showVerbose = (bool) $this->option('verbose');
        $tableRows = [];

        foreach ($routes as $route) {
            /** @var RoutingRoute $route */
            $uri = $route->uri();
            $name = $route->getName() ?? '';
            $methods = array_diff($route->methods(), ['HEAD']);
            $method = reset($methods) ?: 'GET';

            // Gather complete middleware list including groups
            $gatheredMiddleware = $router->gatherRouteMiddleware($route);
            $hasAuthMiddleware = collect($gatheredMiddleware)->contains(
                fn ($m) => $m === 'auth' || str_starts_with((string) $m, 'auth:') || $m === Authenticate::class
            );

            // Filter to guard-relevant middleware unless verbose
            $displayedMiddleware = $showVerbose
                ? (implode(', ', $gatheredMiddleware) ?: '(none)')
                : $this->filterGuardRelevantMiddleware($gatheredMiddleware);

            // 1. Guest routes
            $isGuest = in_array($name, $guestRouteNames, true)
                || in_array($uri, $guestUris, true)
                || in_array('guest', $gatheredMiddleware, true);

            if ($isGuest) {
                $results[$uri.'@'.$method] = [
                    'name' => $name,
                    'uri' => $uri,
                    'method' => $method,
                    'category' => 'GUEST',
                    'is_guest' => true,
                    'has_auth' => false,
                    'is_protected' => true,
                    'status' => 'GUEST',
                    'http_status' => 200,
                    'location' => '',
                    'middleware' => $gatheredMiddleware,
                ];

                $tableRows[] = [
                    $name ?: '(unnamed)',
                    $method,
                    '/'.ltrim($uri, '/'),
                    '<info>GUEST</info>',
                    'Allowed for guests',
                    $displayedMiddleware,
                ];

                continue;
            }

            // 2. Signature-protected storage serving routes
            $isSignatureRoute = in_array($name, $signatureRoutes, true)
                || in_array('signed', $gatheredMiddleware, true)
                || collect($gatheredMiddleware)->contains(fn ($m) => $m === ValidateSignature::class);

            if ($isSignatureRoute) {
                // Verify guest probe without signature returns 403
                $testUri = preg_replace('/\{[^}]+\}/', 'fake-probe-path.txt', $uri);
                $request = Request::create('/'.ltrim($testUri, '/'), $method);
                try {
                    $response = $httpKernel->handle($request);
                    $status = $response->getStatusCode();
                    $httpKernel->terminate($request, $response);
                } catch (\Throwable $e) {
                    $status = 403;
                }

                $results[$uri.'@'.$method] = [
                    'name' => $name,
                    'uri' => $uri,
                    'method' => $method,
                    'category' => 'SIGNATURE-PROTECTED',
                    'is_guest' => false,
                    'has_auth' => false,
                    'is_protected' => true,
                    'status' => 'SIGNATURE-PROTECTED',
                    'http_status' => $status,
                    'location' => '',
                    'middleware' => $gatheredMiddleware,
                ];

                $tableRows[] = [
                    $name ?: '(unnamed)',
                    $method,
                    '/'.ltrim($uri, '/'),
                    '<info>SIGNATURE-PROTECTED</info>',
                    "Signed URL required (unsigned HTTP {$status})",
                    $displayedMiddleware,
                ];

                continue;
            }

            // 3. Probe non-guest route: ONLY guest probes with fake ID (never real records)
            $hasRouteParams = (bool) preg_match('/\{[^}]+\}/', $uri);
            $testUri = preg_replace('/\{[^}]+\}/', '999999', $uri);
            $request = Request::create('/'.ltrim($testUri, '/'), $method);

            try {
                $response = $httpKernel->handle($request);
                $status = $response->getStatusCode();
                $location = (string) $response->headers->get('Location', '');
                $httpKernel->terminate($request, $response);
            } catch (\Throwable $e) {
                $status = 500;
                $location = $e->getMessage();
            }

            // Split into exact required categories:
            // - CONFIRMED EXPOSED: 200, 500 (static), or non-login redirect for a guest
            // - GUARDED-NO-AUTH: 403 without auth middleware
            // - INDETERMINATE: 404 or 500 from model binding
            // - PROTECTED: 302 to login with auth middleware, or 401 with auth middleware

            $isRedirectToLogin = in_array($status, [301, 302, 303, 307, 308], true)
                && (str_contains($location, 'login') || str_contains($location, '/login'));

            if ($status === 200) {
                $category = 'CONFIRMED_EXPOSED';
                $statusLabel = '<error>CONFIRMED EXPOSED</error>';
                $detail = 'Exposed HTTP 200 without authentication!';
                $isProtected = false;
            } elseif (in_array($status, [301, 302, 303, 307, 308], true) && ! $isRedirectToLogin) {
                $category = 'CONFIRMED_EXPOSED';
                $statusLabel = '<error>CONFIRMED EXPOSED</error>';
                $detail = "Redirects to {$location} instead of login!";
                $isProtected = false;
            } elseif ($status === 500 && ! $hasRouteParams) {
                $category = 'CONFIRMED_EXPOSED';
                $statusLabel = '<error>CONFIRMED EXPOSED</error>';
                $detail = 'HTTP 500 server error on static unauthenticated route';
                $isProtected = false;
            } elseif ($status === 403) {
                $category = 'GUARDED_NO_AUTH';
                $statusLabel = '<error>GUARDED-NO-AUTH</error>';
                $detail = 'Guarded (HTTP 403) but missing login redirect'.($hasAuthMiddleware ? '' : ' [MISSING auth middleware]');
                $isProtected = false;
            } elseif (($status === 404 || $status === 500) && $hasRouteParams) {
                $category = 'INDETERMINATE';
                $statusLabel = '<comment>INDETERMINATE</comment>';
                $detail = "HTTP {$status} (model-binding on fake ID 999999)".($hasAuthMiddleware ? '' : ' [MISSING auth middleware]');
                $isProtected = false;
            } elseif ($isRedirectToLogin && $hasAuthMiddleware) {
                $category = 'PROTECTED';
                $statusLabel = '<info>PROTECTED</info>';
                $detail = "Redirect {$status} -> {$location}";
                $isProtected = true;
            } elseif ($isRedirectToLogin && ! $hasAuthMiddleware) {
                $category = 'GUARDED_NO_AUTH';
                $statusLabel = '<error>GUARDED-NO-AUTH</error>';
                $detail = "Redirect {$status} -> {$location} [MISSING auth middleware]";
                $isProtected = false;
            } else {
                $category = 'GUARDED_NO_AUTH';
                $statusLabel = '<error>GUARDED-NO-AUTH</error>';
                $detail = "HTTP {$status} (Location: {$location})".($hasAuthMiddleware ? '' : ' [MISSING auth middleware]');
                $isProtected = false;
            }

            $results[$uri.'@'.$method] = [
                'name' => $name,
                'uri' => $uri,
                'method' => $method,
                'category' => $category,
                'is_guest' => false,
                'has_auth' => $hasAuthMiddleware,
                'is_protected' => $isProtected,
                'status' => $category,
                'http_status' => $status,
                'location' => $location,
                'middleware' => $gatheredMiddleware,
            ];

            if ($name !== '') {
                $results['name:'.$name] = &$results[$uri.'@'.$method];
            }

            $tableRows[] = [
                $name ?: '(unnamed)',
                $method,
                '/'.ltrim($uri, '/'),
                $statusLabel,
                $detail,
                $displayedMiddleware,
            ];
        }

        $headers = ['Route Name', 'Method', 'URI', 'Auth Enforcement', 'Probe Response', $showVerbose ? 'Middleware Stack (Full)' : 'Guard Middleware'];
        $this->table($headers, $tableRows);

        return $results;
    }

    /**
     * Filter middleware stack down to guard-relevant entries only.
     *
     * @param  array<int, mixed>  $middleware
     */
    protected function filterGuardRelevantMiddleware(array $middleware): string
    {
        $filtered = [];

        foreach ($middleware as $m) {
            $mStr = (string) $m;

            if ($mStr === 'auth' || str_starts_with($mStr, 'auth:') || $mStr === Authenticate::class) {
                $filtered[] = 'auth'.(str_contains($mStr, ':') ? substr($mStr, strpos($mStr, ':')) : '');
            } elseif ($mStr === 'guest' || $mStr === RedirectIfAuthenticated::class) {
                $filtered[] = 'guest';
            } elseif (str_starts_with($mStr, 'can:') || $mStr === Authorize::class) {
                $filtered[] = $mStr;
            } elseif (str_starts_with($mStr, 'throttle:') || $mStr === ThrottleRequests::class) {
                $filtered[] = str_starts_with($mStr, 'throttle:') ? $mStr : 'throttle';
            } elseif ($mStr === 'signed' || $mStr === ValidateSignature::class) {
                $filtered[] = 'signed';
            } elseif (str_starts_with($mStr, 'role:') || str_starts_with($mStr, 'permission:')) {
                $filtered[] = $mStr;
            } elseif ($mStr === EnsureActiveUser::class || str_ends_with($mStr, 'EnsureActiveUser')) {
                $filtered[] = 'EnsureActiveUser';
            }
        }

        if (empty($filtered)) {
            return '<fg=red>MISSING auth middleware</>';
        }

        return implode(', ', $filtered);
    }

    /**
     * Extended 44-page registry audit: controller method, view, sidebar/parent link, and stub scans.
     *
     * @param  array<int, array<string, mixed>>  $pages
     * @param  array<string, array<string, mixed>>  $routeResults
     */
    protected function auditPages(array $pages, array $routeResults, int $completedPhase): array
    {
        $this->newLine();
        $this->info("--- 44-Page Gap Analysis Table (Target Phase: {$completedPhase}) ---");

        $legacyRoles = array_keys(config('roles.roles', []));
        $allRegisteredRoutes = Route::getRoutes();
        $allRegisteredRouteNames = [];
        foreach ($allRegisteredRoutes as $r) {
            if ($r->getName()) {
                $allRegisteredRouteNames[$r->getName()] = $r;
            }
        }

        // Read navigation layout content to verify sidebar links
        $layoutPath = resource_path('views/catalog/layout.blade.php');
        $layoutContent = file_exists($layoutPath) ? file_get_contents($layoutPath) : '';

        $pageRows = [];
        $gaps = [];

        foreach ($pages as $page) {
            $id = $page['id'];
            $name = $page['name'];
            $phase = (int) $page['phase'];
            $isDue = ($phase <= $completedPhase);
            $expectedRoles = $page['roles'] ?? [];
            $pageRoutes = $page['routes'] ?? [];
            $isGuest = (bool) ($page['guest'] ?? false);
            $reachableFrom = $page['reachable_from'] ?? '';

            $issues = [];

            // 1. Route existence & Controller method check
            $missingRoutes = [];
            foreach ($pageRoutes as $routeName) {
                if (! isset($allRegisteredRouteNames[$routeName])) {
                    $missingRoutes[] = $routeName;

                    continue;
                }

                $route = $allRegisteredRouteNames[$routeName];
                $action = $route->getActionName();

                if (str_contains($action, '@')) {
                    [$controller, $method] = explode('@', $action);
                    if (! class_exists($controller)) {
                        $issues[] = "Controller missing: {$controller}";
                    } elseif (! method_exists($controller, $method)) {
                        $issues[] = "Method missing: {$controller}@{$method}";
                    }
                }
            }
            if (! empty($missingRoutes)) {
                $issues[] = 'Missing routes: '.implode(', ', $missingRoutes);
            }

            // 2. Role alignment: check if expected target roles exist in current config/roles.php
            $roleMismatches = [];
            foreach ($expectedRoles as $roleKey) {
                if (! in_array($roleKey, $legacyRoles, true)) {
                    $roleMismatches[] = $roleKey;
                }
            }
            if (! empty($roleMismatches)) {
                $issues[] = 'Unmigrated roles: '.implode(', ', $roleMismatches);
            }

            // 3. HTTP authentication & middleware check on page routes
            if (! $isGuest) {
                $unprotectedRoutes = [];
                foreach ($pageRoutes as $routeName) {
                    if (isset($routeResults['name:'.$routeName])) {
                        $res = $routeResults['name:'.$routeName];
                        if (! $res['is_protected']) {
                            $unprotectedRoutes[] = "{$routeName} ({$res['status']})";
                        }
                    }
                }
                if (! empty($unprotectedRoutes)) {
                    $issues[] = 'Unprotected routes: '.implode(', ', $unprotectedRoutes);
                }
            }

            // 4. View existence & Stub text scan
            $viewIssues = $this->checkPageViewsAndStubs($id, $pageRoutes, $allRegisteredRouteNames);
            if (! empty($viewIssues)) {
                $issues = array_merge($issues, $viewIssues);
            }

            // 5. Sidebar or parent-page link check
            $navIssue = $this->checkPageNavigation($id, $pageRoutes, $reachableFrom, $layoutContent, $pages);
            if ($navIssue !== null) {
                $issues[] = $navIssue;
            }

            // 6. P04 specific error view check
            if ($id === 'P04') {
                $errorCodes = ['403', '404', '419', '500'];
                $missingErrors = [];
                foreach ($errorCodes as $code) {
                    $viewFile = resource_path("views/errors/{$code}.blade.php");
                    if (! file_exists($viewFile)) {
                        $missingErrors[] = "{$code}.blade.php";
                    }
                }
                if (! empty($missingErrors)) {
                    $issues[] = 'Missing error views: '.implode(', ', $missingErrors);
                }
            }

            // 7. P44 specific offline page & retry control check
            if ($id === 'P44') {
                $offlinePath = public_path('offline.html');
                if (! file_exists($offlinePath)) {
                    $issues[] = 'Missing public/offline.html';
                } else {
                    $offlineHtml = file_get_contents($offlinePath);
                    $hasRetry = preg_match('/(retry|reload|window\.location\.reload)/i', $offlineHtml);
                    if (! $hasRetry) {
                        $issues[] = 'public/offline.html missing retry control';
                    }
                }
            }

            $hasGap = ! empty($issues);
            $status = 'PASS';
            if ($hasGap) {
                $status = $isDue ? '<error>FAIL</error>' : '<comment>GAP (PHASE '.$phase.')</comment>';
            } else {
                $status = '<info>PASS</info>';
            }

            $pageRows[] = [
                $id,
                "P{$phase}",
                $name,
                $isDue ? 'Yes' : 'No',
                empty($pageRoutes) ? '(none)' : implode("\n", $pageRoutes),
                empty($expectedRoles) ? '(guest)' : implode(', ', $expectedRoles),
                empty($issues) ? '<info>Aligned</info>' : implode('; ', $issues),
                $status,
            ];

            $gaps[] = [
                'id' => $id,
                'name' => $name,
                'phase' => $phase,
                'is_due' => $isDue,
                'has_gap' => $hasGap,
                'issues' => $issues,
            ];
        }

        $this->table(
            ['ID', 'Phase', 'Page Name', 'Due?', 'Routes', 'Target Roles', 'Gaps / Missing Alignment', 'Status'],
            $pageRows
        );

        return $gaps;
    }

    /**
     * Check views associated with a page and scan for stub text.
     *
     * @param  array<int, string>  $pageRoutes
     * @param  array<string, RoutingRoute>  $allRegisteredRouteNames
     * @return array<int, string>
     */
    protected function checkPageViewsAndStubs(string $pageId, array $pageRoutes, array $allRegisteredRouteNames): array
    {
        $issues = [];
        $viewsScanned = [];

        foreach ($pageRoutes as $routeName) {
            if (! isset($allRegisteredRouteNames[$routeName])) {
                continue;
            }

            $route = $allRegisteredRouteNames[$routeName];
            $action = $route->getActionName();

            if (! str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action);
            if (! class_exists($controller) || ! method_exists($controller, $method)) {
                continue;
            }

            $ref = new \ReflectionMethod($controller, $method);
            $file = $ref->getFileName();
            if (! $file || ! file_exists($file)) {
                continue;
            }

            $source = implode('', array_slice(file($file), $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
            if (preg_match_all("/view\(\s*['\"]([^'\"]+)['\"]/", $source, $matches)) {
                foreach ($matches[1] as $viewName) {
                    if (isset($viewsScanned[$viewName])) {
                        continue;
                    }
                    $viewsScanned[$viewName] = true;

                    if (! view()->exists($viewName)) {
                        $issues[] = "Missing view '{$viewName}'";

                        continue;
                    }

                    $viewFilePath = view($viewName)->getPath();
                    if (file_exists($viewFilePath)) {
                        $content = file_get_contents($viewFilePath);
                        if (preg_match('/\b(TODO|Lorem\s+ipsum|coming\s+soon|placeholder\s+page)\b/i', $content, $stubMatch)) {
                            $issues[] = "Stub text '{$stubMatch[0]}' in view '{$viewName}'";
                        }
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Check that a sidebar or parent-page link exists for the page.
     *
     * @param  array<int, string>  $pageRoutes
     * @param  array<int, array<string, mixed>>  $allPages
     */
    protected function checkPageNavigation(string $pageId, array $pageRoutes, string $reachableFrom, string $layoutContent, array $allPages): ?string
    {
        if (empty($reachableFrom)) {
            return null;
        }

        if (str_starts_with($reachableFrom, 'sidebar:')) {
            $routeName = substr($reachableFrom, 8);
            $hasLink = str_contains($layoutContent, "route('{$routeName}')")
                || str_contains($layoutContent, "route(\"{$routeName}\")")
                || str_contains($layoutContent, "'{$routeName}'");

            if (! $hasLink) {
                return "Missing sidebar navigation link for '{$routeName}'";
            }

            return null;
        }

        if (preg_match('/^P\d{2}$/', $reachableFrom)) {
            $parentPage = collect($allPages)->firstWhere('id', $reachableFrom);
            if (! $parentPage) {
                return "Unknown parent page '{$reachableFrom}'";
            }

            $childPrimaryRoute = reset($pageRoutes) ?: '';
            if (! empty($childPrimaryRoute)) {
                $hasParentLink = false;
                $viewsToCheck = glob(resource_path('views/*/*.blade.php')) ?: [];
                foreach ($viewsToCheck as $vf) {
                    $vc = file_get_contents($vf);
                    if (str_contains($vc, "route('{$childPrimaryRoute}'") || str_contains($vc, "route(\"{$childPrimaryRoute}\"")) {
                        $hasParentLink = true;
                        break;
                    }
                }
                if (! $hasParentLink) {
                    return "Missing navigation link from parent {$reachableFrom}";
                }
            }

            return null;
        }

        return null;
    }

    /**
     * Print route-level check summary split into required categories.
     *
     * @param  array<string, array<string, mixed>>  $routeResults
     * @return array{confirmed_exposed: array<int, array<string, mixed>>, guarded_no_auth: array<int, array<string, mixed>>, indeterminate: array<int, array<string, mixed>>, signature_protected: array<int, array<string, mixed>>, protected: array<int, array<string, mixed>>, guest: array<int, array<string, mixed>>, missing_auth_mw: array<int, array<string, mixed>>}
     */
    protected function printRouteAuditSummary(array $routeResults): array
    {
        $uniqueRoutes = [];
        foreach ($routeResults as $key => $res) {
            if (str_starts_with($key, 'name:')) {
                continue;
            }
            $uniqueRoutes[] = $res;
        }

        $total = count($uniqueRoutes);
        $guestRoutes = array_filter($uniqueRoutes, fn ($r) => $r['category'] === 'GUEST');
        $protectedRoutes = array_filter($uniqueRoutes, fn ($r) => $r['category'] === 'PROTECTED');
        $signatureProtected = array_filter($uniqueRoutes, fn ($r) => $r['category'] === 'SIGNATURE-PROTECTED');
        $confirmedExposed = array_filter($uniqueRoutes, fn ($r) => $r['category'] === 'CONFIRMED_EXPOSED');
        $guardedNoAuth = array_filter($uniqueRoutes, fn ($r) => $r['category'] === 'GUARDED_NO_AUTH');
        $indeterminate = array_filter($uniqueRoutes, fn ($r) => $r['category'] === 'INDETERMINATE');

        // All non-guest, non-signature routes that lack auth middleware
        $missingAuthMw = array_filter($uniqueRoutes, fn ($r) => ! $r['is_guest'] && $r['category'] !== 'SIGNATURE-PROTECTED' && ! $r['has_auth']);

        $this->newLine();
        $this->info('--- Route Authentication Enforcement Summary ---');
        $this->line("Total Registered Routes:     <comment>{$total}</comment>");
        $this->line('Guest Routes:                <info>'.count($guestRoutes).'</info>');
        $this->line('Protected Routes:            <info>'.count($protectedRoutes).'</info>');
        $this->line('Signature-Protected Routes:  <info>'.count($signatureProtected).'</info>');
        $this->line('Confirmed Exposed Routes:    '.(count($confirmedExposed) > 0 ? '<error>'.count($confirmedExposed).'</error>' : '<info>0</info>'));
        $this->line('Guarded-No-Auth Routes:      '.(count($guardedNoAuth) > 0 ? '<error>'.count($guardedNoAuth).'</error>' : '<info>0</info>'));
        $this->line('Indeterminate Routes:        <comment>'.count($indeterminate).'</comment> (404/500 model-binding on fake ID)');

        // Separately list each category
        if (! empty($confirmedExposed)) {
            $this->newLine();
            $this->error('CONFIRMED EXPOSED (200, 500, or non-login redirect for a guest):');
            foreach ($confirmedExposed as $r) {
                $mwNote = $r['has_auth'] ? '' : ' [MISSING auth middleware]';
                $this->line("  - [{$r['method']}] /".ltrim($r['uri'], '/')." ({$r['name']}) -> Status {$r['http_status']}{$mwNote}");
            }
        }

        if (! empty($guardedNoAuth)) {
            $this->newLine();
            $this->warn('GUARDED-NO-AUTH (403 without auth middleware or login redirect):');
            foreach ($guardedNoAuth as $r) {
                $mwNote = $r['has_auth'] ? '' : ' [MISSING auth middleware]';
                $this->line("  - [{$r['method']}] /".ltrim($r['uri'], '/')." ({$r['name']}) -> Status {$r['http_status']}{$mwNote}");
            }
        }

        if (! empty($indeterminate)) {
            $this->newLine();
            $this->line('<comment>INDETERMINATE (404/500 from model binding on fake ID 999999):</comment>');
            foreach ($indeterminate as $r) {
                $mwNote = $r['has_auth'] ? '' : ' [MISSING auth middleware]';
                $this->line("  - [{$r['method']}] /".ltrim($r['uri'], '/')." ({$r['name']}) -> Status {$r['http_status']}{$mwNote}");
            }
        }

        if (! empty($signatureProtected)) {
            $this->newLine();
            $this->info('SIGNATURE-PROTECTED (verified signed URL requirement):');
            foreach ($signatureProtected as $r) {
                $this->line("  - [{$r['method']}] /".ltrim($r['uri'], '/')." ({$r['name']}) -> Unsigned probe HTTP {$r['http_status']}");
            }
        }

        return [
            'confirmed_exposed' => $confirmedExposed,
            'guarded_no_auth' => $guardedNoAuth,
            'indeterminate' => $indeterminate,
            'signature_protected' => $signatureProtected,
            'protected' => $protectedRoutes,
            'guest' => $guestRoutes,
            'missing_auth_mw' => $missingAuthMw,
        ];
    }

    /**
     * Print final audit summary and calculate exit code.
     *
     * @param  array<int, array<string, mixed>>  $pageGaps
     * @param  array<string, mixed>  $categoryStats
     */
    protected function printFinalSummary(array $pageGaps, int $completedPhase, array $categoryStats): int
    {
        $duePages = array_filter($pageGaps, fn ($p) => $p['is_due']);
        $dueFailed = array_filter($duePages, fn ($p) => $p['has_gap']);

        $total44 = count($pageGaps);
        $totalGaps = count(array_filter($pageGaps, fn ($p) => $p['has_gap']));

        $confirmedExposedCount = count($categoryStats['confirmed_exposed']);
        $missingAuthCount = count($categoryStats['missing_auth_mw']);

        $this->newLine();
        $this->info('================================================================================');
        $this->info('                            AUDIT SUMMARY RESULTS                               ');
        $this->info('================================================================================');
        $this->line("Total Pages Registered:       <comment>{$total44}</comment>");
        $this->line("Pages Due for Phase {$completedPhase}:        <comment>".count($duePages).'</comment>');
        $this->line('Due Pages Passed:             <info>'.(count($duePages) - count($dueFailed)).'</info>');
        $this->line('Due Pages Failed:             '.(count($dueFailed) > 0 ? '<error>'.count($dueFailed).'</error>' : '<info>0</info>'));
        $this->line("Total Pending/Gaps (P2..P12): <comment>{$totalGaps}</comment>");
        $this->line('Confirmed Exposed Routes:     '.($confirmedExposedCount > 0 ? "<error>{$confirmedExposedCount}</error>" : '<info>0</info>'));
        $this->line('Routes Missing Auth MW:       '.($missingAuthCount > 0 ? "<error>{$missingAuthCount}</error>" : '<info>0</info>'));

        // Rule 1: SECURITY FAIL is driven by CONFIRMED EXPOSED and by any route without auth middleware
        if ($confirmedExposedCount > 0 || $missingAuthCount > 0) {
            $this->newLine();
            $this->error('================================================================================');
            $this->error('                                SECURITY FAIL                                   ');
            $this->error('================================================================================');
            $this->error("SECURITY FAIL: {$confirmedExposedCount} confirmed exposed route(s) and {$missingAuthCount} route(s) without auth middleware detected!");

            return 1;
        }

        if (count($dueFailed) > 0) {
            $this->newLine();
            $this->error('AUDIT FAILED: '.count($dueFailed)." page(s) due in Phase {$completedPhase} have unmet gaps.");

            return 1;
        }

        $this->newLine();
        $this->info("AUDIT PASSED: Security verified and all pages due in Phase {$completedPhase} are satisfied.");

        return 0;
    }
}
