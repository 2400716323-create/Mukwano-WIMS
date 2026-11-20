<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Display alerts to authorised operational and audit roles.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user && (
                $user->isAdministrator()
                || $user->isWarehouseManager()
                || $user->isStorekeeper()
                || $user->isProcurementOfficer()
                || $user->isDispatchOfficer()
                || $user->isAuditor()
            ),
            403,
            'You do not have permission to view alerts.'
        );

        $filters = $request->validate([
            'type' => ['nullable', 'in:low_stock,expiry,pending_purchase_order,pending_stock_count'],
            'severity' => ['nullable', 'in:critical,high,medium,low'],
            'state' => ['nullable', 'in:open,resolved,all'],
        ]);

        $state = $filters['state'] ?? 'open';

        $alerts = Alert::query()
            ->when(
                $filters['type'] ?? null,
                fn ($query, string $type) => $query->where('type', $type)
            )
            ->when(
                $filters['severity'] ?? null,
                fn ($query, string $severity) => $query->where('severity', $severity)
            )
            ->when(
                $state === 'open',
                fn ($query) => $query
                    ->whereNull('resolved_at')
                    ->where('condition_active', true)
            )
            ->when(
                $state === 'resolved',
                fn ($query) => $query->whereNotNull('resolved_at')
            )
            ->orderByRaw(
                "CASE severity
                    WHEN 'critical' THEN 0
                    WHEN 'high' THEN 1
                    WHEN 'medium' THEN 2
                    ELSE 3
                END"
            )
            ->orderByDesc('last_seen_at')
            ->paginate(25)
            ->withQueryString();

        /*
         * Expiry metrics.
         *
         * Critical = expired stock.
         * High = within the configured soon window (normally 30 days).
         * Medium = within the configured warning window (normally 60 days).
         * Low = within the configured notice window (normally 90 days).
         */
        $activeExpiryAlerts = fn (string $severity): int => Alert::query()
            ->where('type', 'expiry')
            ->where('severity', $severity)
            ->whereNull('resolved_at')
            ->where('condition_active', true)
            ->count();

        $metrics = [
            'critical' => Alert::query()
                ->where('severity', 'critical')
                ->whereNull('resolved_at')
                ->where('condition_active', true)
                ->count(),

            'high' => Alert::query()
                ->where('severity', 'high')
                ->whereNull('resolved_at')
                ->where('condition_active', true)
                ->count(),

            'low_stock' => Alert::query()
                ->where('type', 'low_stock')
                ->whereNull('resolved_at')
                ->where('condition_active', true)
                ->count(),

            'expiry' => Alert::query()
                ->where('type', 'expiry')
                ->whereNull('resolved_at')
                ->where('condition_active', true)
                ->count(),

            // Near-expiry categories.
            'expired' => $activeExpiryAlerts('critical'),
            'expiry_30' => $activeExpiryAlerts('high'),
            'expiry_60' => $activeExpiryAlerts('medium'),
            'expiry_90' => $activeExpiryAlerts('low'),
        ];

        return view('alerts.index', compact(
            'alerts',
            'filters',
            'state',
            'metrics'
        ));
    }

    /**
     * Display only expiry alerts to authorised operational roles.
     *
     * P36 permissions:
     * Administrator, Warehouse Manager, Storekeeper and Dispatch Officer.
     */
    public function nearExpiry(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user && (
                $user->isAdministrator()
                || $user->isWarehouseManager()
                || $user->isStorekeeper()
                || $user->isDispatchOfficer()
            ),
            403,
            'You do not have permission to view near-expiry alerts.'
        );

        // Force the expiry filter so users cannot change this view to another alert type.
        $request->merge([
            'type' => 'expiry',
        ]);

        return $this->index($request);
    }

    /**
     * Only administrators and warehouse managers may resolve alerts.
     */
    public function resolve(Request $request, Alert $alert): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user && (
                $user->isAdministrator()
                || $user->isWarehouseManager()
            ),
            403,
            'You do not have permission to resolve alerts.'
        );

        abort_unless(
            $alert->condition_active && $alert->resolved_at === null,
            409,
            'This alert is no longer active.'
        );

        $alert->forceFill([
            'resolved_at' => now(),
            'resolved_by' => $user->id,
        ])->save();

        return back()->with('status', 'Alert resolved.');
    }
}
