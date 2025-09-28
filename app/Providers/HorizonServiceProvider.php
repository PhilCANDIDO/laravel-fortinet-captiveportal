<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Laravel\Horizon\Events\MasterSupervisorLooped;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
        
        // Configure Horizon authorization
        Horizon::auth(function ($request) {
            // Check if the user is authenticated as admin
            return auth()->guard('admin')->check();
        });
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            // Always check for admin authentication, even in local environment
            // This ensures Horizon is protected at all times
            
            // Check if user is authenticated as admin
            if (!auth()->guard('admin')->check()) {
                return false;
            }
            
            // Get the authenticated admin user
            $admin = auth()->guard('admin')->user();
            
            // Optional: Restrict to super_admin role only
            // Uncomment the following line to restrict access to super_admin only
            // return $admin->role === 'super_admin';
            
            // Allow all authenticated admin users
            return true;
        });
    }
}
