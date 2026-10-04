<?php

namespace App\Providers;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\PaymentSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        Password::defaults(function () {
            return Password::min(8)->letters()->numbers();
        });

        RateLimiter::for('pos-sync', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        Gate::define('manage_users', function ($user) {
            return $user->hasPermission('manage_users');
        });

        Gate::define('manage_delivery_locations', function ($user) {
            return $user->hasPermission('manage_delivery_locations');
        });

        Gate::define('manage_bank_accounts', function ($user) {
            return $user->hasPermission('manage_bank_accounts');
        });

        Gate::define('process_orders', function ($user) {
            return $user->hasPermission('process_orders');
        });

        Gate::define('modify_items', function ($user) {
            return $user->hasPermission('modify_items');
        });

        Gate::define('manage_categories', function ($user) {
            return $user->hasPermission('manage_categories');
        });

        Gate::define('view_sync', function ($user) {
            return $user->hasPermission('view_sync');
        });

        Gate::define('manage_payment_settings', function ($user) {
            return $user->hasPermission('manage_payment_settings');
        });

        Gate::define('view_revenue', function ($user) {
            return $user->hasPermission('view_revenue');
        });

        Gate::define('manage_complaints', function ($user) {
            return $user->hasPermission('manage_complaints');
        });

        Gate::define('manage_discounts', function ($user) {
            return $user->hasPermission('manage_discounts');
        });

        Gate::define('manage_chat', function ($user) {
            return $user->hasPermission('manage_chat');
        });

        View::composer('*', function ($view) {
            if ($view->offsetExists('activePaymentSettings')) {
                return;
            }
            try {
                $view->with('activePaymentSettings', PaymentSettings::getActive());
            } catch (\Throwable $e) {
                $view->with('activePaymentSettings', null);
            }
        });

        View::composer('layouts.main', function ($view) {
            try {
                if (Auth::check()) {
                    $cartCount = CartItem::where('user_id', Auth::id())->sum('quantity');
                } else {
                    $cartCount = CartItem::where('session_id', session()->get('cart_session_id'))->sum('quantity');
                }
                $view->with('cartCount', $cartCount);
            } catch (\Throwable $e) {
                $view->with('cartCount', 0);
            }

            try {
                $view->with('navCategories', Category::where('is_active', true)
                    ->where('show_on_home', true)
                    ->whereHas('items')
                    ->orderBy('name')
                    ->get());
            } catch (\Throwable $e) {
                $view->with('navCategories', collect());
            }
        });
    }
}