<?php

namespace App\Providers;

use App\Models\Discount;
use App\Models\Order;
use App\Models\User;
use App\Models\WebHook;
use App\Policies\AuthenticatedPolicy;
use App\Policies\DiscountPolicy;
use App\Policies\OrderPolicy;
use App\Policies\UserPolicy;
use App\Policies\WebHookPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        WebHook::class => WebHookPolicy::class,
        Order::class => OrderPolicy::class,
        User::class => UserPolicy::class,
        Discount::class => DiscountPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('authenticated', [AuthenticatedPolicy::class, 'authenticated']);

        Password::defaults(function () {
            return Password::min(Config::get('validation.min_length'))
                ->uncompromised();
        });
    }
}
