<?php

namespace App\Providers;

use App\Models\User;
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
        // The member login dropdown (layout) must only list active members,
        // regardless of how each controller builds its own $members list.
        View::composer('layouts.mc', function ($view) {
            $view->with('loginMembers', User::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']));
        });
    }
}
