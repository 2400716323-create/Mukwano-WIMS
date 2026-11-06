<?php

namespace App\Http\Controllers;

use App\Models\InventorySetting;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SystemSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = InventorySetting::current();

        return view('administration.settings.edit', [
            'settings' => $settings,
            'expiryWindows' => $settings->expiryWindows(),
        ]);
    }

    public function update(Request $request, AuditService $auditService): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'variance_threshold_percent' => ['required', 'numeric', 'gte:0', 'lte:1000', 'decimal:0,3'],
            'accuracy_tolerance_percent' => ['required', 'numeric', 'gte:0', 'lte:100', 'decimal:0,3'],
            'expiry_soon_days' => ['required', 'integer', 'min:1', 'max:365'],
            'expiry_warning_days' => ['required', 'integer', 'min:2', 'max:365', 'gt:expiry_soon_days'],
            'expiry_notice_days' => ['required', 'integer', 'min:3', 'max:365', 'gt:expiry_warning_days'],
        ]);
        $settings = InventorySetting::current();
        $oldValues = $settings->only([
            'company_name',
            'logo_path',
            'variance_threshold_percent',
            'accuracy_tolerance_percent',
            'expiry_windows',
        ]);
        $oldLogoPath = $settings->logo_path;
        $newLogoPath = $oldLogoPath;
        if ($request->hasFile('logo')) {
            $newLogoPath = $request->file('logo')->store('system', 'public');
            if ($newLogoPath === false) {
                throw new RuntimeException('The company logo could not be stored.');
            }
        } elseif ($request->boolean('remove_logo')) {
            $newLogoPath = null;
        }

        try {
            DB::transaction(function () use ($settings, $validated, $newLogoPath, $oldValues, $auditService): void {
                $settings->forceFill([
                    'company_name' => $validated['company_name'],
                    'logo_path' => $newLogoPath,
                    'variance_threshold_percent' => $validated['variance_threshold_percent'],
                    'accuracy_tolerance_percent' => $validated['accuracy_tolerance_percent'],
                    'expiry_windows' => [
                        'soon' => (int) $validated['expiry_soon_days'],
                        'warning' => (int) $validated['expiry_warning_days'],
                        'notice' => (int) $validated['expiry_notice_days'],
                    ],
                    'updated_by' => request()->user()->id,
                ])->save();
                $auditService->record($settings, 'system_settings_updated', $oldValues, [
                    'company_name' => $settings->company_name,
                    'logo_path' => $settings->logo_path,
                    'variance_threshold_percent' => $settings->variance_threshold_percent,
                    'accuracy_tolerance_percent' => $settings->accuracy_tolerance_percent,
                    'expiry_windows' => $settings->expiry_windows,
                ]);
            });
        } catch (Throwable $exception) {
            if ($newLogoPath !== $oldLogoPath && $newLogoPath !== null) {
                Storage::disk('public')->delete($newLogoPath);
            }

            throw $exception;
        }

        if ($newLogoPath !== $oldLogoPath && $oldLogoPath !== null
            && Storage::disk('public')->exists($oldLogoPath)
            && ! Storage::disk('public')->delete($oldLogoPath)) {
            throw new RuntimeException('The previous company logo could not be removed.');
        }

        return to_route('admin.settings.edit')->with('status', 'System settings updated.');
    }
}
