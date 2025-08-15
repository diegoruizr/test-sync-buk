<?php

namespace App\Providers;

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
        \App\Models\Department::observe(\App\Observers\DepartmentObserver::class);
        \App\Models\Employee::observe(\App\Observers\EmployeeObserver::class);
        \App\Models\EmployeeSkill::observe(\App\Observers\EmployeeSkillObserver::class);
        \App\Models\Skill::observe(\App\Observers\SkillObserver::class);
    }
}
