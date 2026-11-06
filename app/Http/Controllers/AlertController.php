<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:low_stock,expiry,pending_purchase_order,pending_stock_count'],
            'severity' => ['nullable', 'in:critical,high,medium,low'],
            'state' => ['nullable', 'in:open,resolved,all'],
        ]);
        $state = $filters['state'] ?? 'open';
        $alerts = Alert::query()
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['severity'] ?? null, fn ($query, string $severity) => $query->where('severity', $severity))
            ->when($state === 'open', fn ($query) => $query->whereNull('resolved_at')->where('condition_active', true))
            ->when($state === 'resolved', fn ($query) => $query->whereNotNull('resolved_at'))
            ->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderByDesc('last_seen_at')
            ->paginate(25)
            ->withQueryString();

        $metrics = [
            'critical' => Alert::query()->where('severity', 'critical')->whereNull('resolved_at')->where('condition_active', true)->count(),
            'high' => Alert::query()->where('severity', 'high')->whereNull('resolved_at')->where('condition_active', true)->count(),
            'low_stock' => Alert::query()->where('type', 'low_stock')->whereNull('resolved_at')->where('condition_active', true)->count(),
            'expiry' => Alert::query()->where('type', 'expiry')->whereNull('resolved_at')->where('condition_active', true)->count(),
        ];

        return view('alerts.index', compact('alerts', 'filters', 'state', 'metrics'));
    }

    public function resolve(Request $request, Alert $alert): RedirectResponse
    {
        abort_unless($alert->condition_active && $alert->resolved_at === null, 409);

        $alert->forceFill([
            'resolved_at' => now(),
            'resolved_by' => $request->user()?->id,
        ])->save();

        return back()->with('status', 'Alert resolved.');
    }
}
