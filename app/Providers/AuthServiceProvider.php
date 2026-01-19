<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // admin manage
        Gate::define('dashboard-admin', function ($user) {
            return in_array($user->role, ['admin']);
        });

        Gate::define('karyawan-sync', function ($user) {
            return in_array($user->role, ['admin']);
        });
        Gate::define('company-sync', function ($user) {
            return in_array($user->role, ['admin']);
        });
        Gate::define('ptkp-sync', function ($user) {
            return in_array($user->role, ['admin']);
        });
        Gate::define('manage-users', function ($user) {
            return in_array($user->role, ['admin']);
        });
        Gate::define('ptkp-history-sync', function ($user) {
            return in_array($user->role, ['admin']);
        });
        Gate::define('periode-karyawan', function ($user) {
            return in_array($user->role, ['admin']);
        });
        Gate::define('jenis-ter-sync', function ($user) {
            return in_array($user->role, ['admin']);
        });
        Gate::define('range-bruto-sync', function ($user) {
            return in_array($user->role, ['admin']);
        });
        Gate::define('pph21-tax-brackets', function ($user) {
            return in_array($user->role, ['admin']);
        });
    }
}
