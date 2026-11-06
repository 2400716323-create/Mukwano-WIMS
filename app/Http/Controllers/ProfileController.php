<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request, AuditService $auditService): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);
        $oldValues = $user->only(['name', 'email']);
        DB::transaction(function () use ($user, $validated, $oldValues, $auditService): void {
            $user->fill($validated)->save();
            $auditService->record($user, 'profile_updated', $oldValues, $user->only(['name', 'email']));
        });

        return to_route('profile.edit')->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request, AuditService $auditService): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        $user = $request->user();
        DB::transaction(function () use ($user, $validated, $auditService): void {
            $user->forceFill(['password' => $validated['password']])->save();
            $auditService->record($user, 'password_changed', [], ['password_changed' => true]);
        });

        return to_route('profile.edit')->with('status', 'Password updated.');
    }
}
