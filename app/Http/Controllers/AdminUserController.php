<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $metrics = [
            'total' => User::query()->count(),
            'active' => User::query()->where('is_active', true)->count(),
            'admins' => User::query()->whereIn('role', ['admin', 'administrator'])->count(),
            'inactive' => User::query()->where('is_active', false)->count(),
        ];

        return view('administration.users.index', [
            'users' => User::query()->orderBy('name')->paginate(25),
            'roles' => config('roles.roles'),
            'metrics' => $metrics,
        ]);
    }

    public function store(Request $request, AuditService $auditService): RedirectResponse
    {
        $allowedRoles = array_merge(array_keys(config('roles.roles')), ['admin', 'supervisor', 'user']);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in($allowedRoles)],
        ]);
        DB::transaction(function () use ($validated, $auditService): void {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $validated['role'],
                'is_active' => true,
            ]);
            $auditService->record($user, 'created', [], [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ]);
        });

        return to_route('admin.users.index')->with('status', 'User account created.');
    }

    public function updateRole(
        Request $request,
        User $user,
        AuditService $auditService,
    ): RedirectResponse {
        $allowedRoles = array_merge(array_keys(config('roles.roles')), ['admin', 'supervisor', 'user']);
        $validated = $request->validate([
            'role' => ['required', Rule::in($allowedRoles)],
        ]);
        $roleChanged = DB::transaction(function () use ($user, $validated, $auditService): bool {
            $this->lockActiveAdministrators();
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $oldRole = $lockedUser->role;
            if ($oldRole === $validated['role']) {
                return false;
            }

            $this->protectLastActiveAdministrator($lockedUser, $validated['role'], $lockedUser->is_active);
            $lockedUser->forceFill(['role' => $validated['role']])->save();
            $auditService->record($lockedUser, 'role_changed', ['role' => $oldRole], [
                'role' => $lockedUser->role,
            ]);

            return true;
        });
        if (! $roleChanged) {
            return to_route('admin.users.index')->with('status', 'User role is unchanged.');
        }

        return to_route('admin.users.index')->with('status', 'User role updated.');
    }

    public function updateStatus(Request $request, User $user, AuditService $auditService): RedirectResponse
    {
        $isActive = DB::transaction(function () use ($request, $user, $auditService): bool {
            $this->lockActiveAdministrators();
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($lockedUser->is_active && $request->user()->is($lockedUser)) {
                throw ValidationException::withMessages([
                    'user' => 'You cannot deactivate your own account.',
                ]);
            }

            $oldStatus = $lockedUser->is_active;
            if ($oldStatus && $lockedUser->isAdministrator()) {
                $this->protectLastActiveAdministrator($lockedUser, $lockedUser->role, false);
            }
            $lockedUser->forceFill(['is_active' => ! $oldStatus])->save();
            $auditService->record($lockedUser, $lockedUser->is_active ? 'activated' : 'deactivated', [
                'is_active' => $oldStatus,
            ], [
                'is_active' => $lockedUser->is_active,
            ]);

            return $lockedUser->is_active;
        });

        return to_route('admin.users.index')->with('status', $isActive ? 'User account activated.' : 'User account deactivated.');
    }

    private function lockActiveAdministrators(): void
    {
        User::query()
            ->whereIn('role', [User::ROLE_ADMINISTRATOR, 'admin'])
            ->where('is_active', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
    }

    private function protectLastActiveAdministrator(User $user, string $newRole, bool $isActive): void
    {
        $isDemoting = ! in_array($newRole, [User::ROLE_ADMINISTRATOR, 'admin'], true);
        if ($user->isAdministrator() && ($isDemoting || ! $isActive)) {
            $otherActiveAdministrators = User::query()
                ->whereIn('role', [User::ROLE_ADMINISTRATOR, 'admin'])
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->count();
            if ($otherActiveAdministrators === 0) {
                throw ValidationException::withMessages([
                    'role' => 'The last active administrator cannot be demoted.',
                ]);
            }
        }
    }
}
