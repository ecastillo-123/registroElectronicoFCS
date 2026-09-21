<?php

namespace App\Providers;

use App\Models\CheckIn;
use App\Models\Company;
use App\Models\CorrectionRequest;
use App\Models\Employee;
use App\Models\Incident;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkCenter;
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        foreach ([CheckIn::class, Company::class, CorrectionRequest::class, Employee::class, Incident::class, Role::class, Shift::class, User::class, WorkCenter::class] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
