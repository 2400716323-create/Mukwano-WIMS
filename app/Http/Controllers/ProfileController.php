<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

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
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $oldValues = $user->only(['name', 'email']);
        $oldAvatarPath = $user->avatar_path;
        $newAvatarPath = $oldAvatarPath;

        if ($request->hasFile('avatar')) {
            $newAvatarPath = $request->file('avatar')->store('avatars', 'public');
            if ($newAvatarPath === false) {
                throw new RuntimeException('The profile image could not be stored.');
            }
        } elseif ($request->boolean('remove_avatar')) {
            $newAvatarPath = null;
        }

        try {
            DB::transaction(function () use ($user, $validated, $newAvatarPath, $oldValues, $auditService): void {
                $user->name = $validated['name'];
                $user->email = $validated['email'];
                $user->avatar_path = $newAvatarPath;
                $user->save();

                $auditService->record($user, 'profile_updated', $oldValues, $user->only(['name', 'email']));
            });

            if ($newAvatarPath !== $oldAvatarPath && $oldAvatarPath && Storage::disk('public')->exists($oldAvatarPath)) {
                Storage::disk('public')->delete($oldAvatarPath);
            }
        } catch (Throwable $e) {
            if ($newAvatarPath !== $oldAvatarPath && $newAvatarPath && Storage::disk('public')->exists($newAvatarPath)) {
                Storage::disk('public')->delete($newAvatarPath);
            }

            throw $e;
        }

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
