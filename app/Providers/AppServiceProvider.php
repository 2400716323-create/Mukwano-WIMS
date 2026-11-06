<?php

namespace App\Providers;

use App\Listeners\AuthenticationAuditListener;
use App\Models\InventorySetting;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-administration', fn (User $user): bool => $user->isAdministrator());
        Gate::define('view-audit-log', fn (User $user): bool => $user->isAdministrator() || $user->isAuditor());
        Gate::define('manage-settings', fn (User $user): bool => $user->isAdministrator());

        Event::listen(Login::class, [AuthenticationAuditListener::class, 'login']);
        Event::listen(Logout::class, [AuthenticationAuditListener::class, 'logout']);
        Event::listen(Failed::class, [AuthenticationAuditListener::class, 'failed']);

        View::composer('*', function ($view): void {
            $systemSettings = Schema::hasTable('inventory_settings')
                ? InventorySetting::query()->first()
                : null;
            $view->with('systemSettings', $systemSettings);
        });
    }
}
