<?php

namespace App\Providers;

use App\Events\DiscountCreated;
use App\Events\DiscountDeleted;
use App\Events\DiscountUpdated;
use App\Events\ItemCreated;
use App\Events\ItemDeleted;
use App\Events\ItemUpdated;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Events\OrderUpdatedStatus;
use App\Events\PageCreated;
use App\Events\PageDeleted;
use App\Events\PageUpdated;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductSetCreated;
use App\Events\ProductSetDeleted;
use App\Events\ProductSetUpdated;
use App\Events\ProductUpdated;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Listeners\ItemUpdatedQuantityListener;
use App\Listeners\OrderCreatedListener;
use App\Listeners\OrderUpdatedStatusListener;
use App\Listeners\WebHookEventListener;
use App\Models\Deposit;
use App\Models\Payment;
use App\Observers\DepositObserver;
use App\Observers\PaymentObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        OrderCreated::class => [
            OrderCreatedListener::class,
            WebHookEventListener::class,
        ],
        OrderUpdatedStatus::class => [
            OrderUpdatedStatusListener::class,
            WebHookEventListener::class,
        ],
        // WebHookEvents
        DiscountCreated::class => [
            WebHookEventListener::class,
        ],
        DiscountDeleted::class => [
            WebHookEventListener::class,
        ],
        DiscountUpdated::class => [
            WebHookEventListener::class,
        ],
        ItemCreated::class => [
            WebHookEventListener::class,
        ],
        ItemDeleted::class => [
            WebHookEventListener::class,
        ],
        ItemUpdated::class => [
            WebHookEventListener::class,
        ],
        ItemUpdatedQuantity::class => [
            WebHookEventListener::class,
            ItemUpdatedQuantityListener::class,
        ],
        OrderUpdated::class => [
            WebHookEventListener::class,
        ],
        PageCreated::class => [
            WebHookEventListener::class,
        ],
        PageDeleted::class => [
            WebHookEventListener::class,
        ],
        PageUpdated::class => [
            WebHookEventListener::class,
        ],
        ProductCreated::class => [
            WebHookEventListener::class,
        ],
        ProductDeleted::class => [
            WebHookEventListener::class,
        ],
        ProductUpdated::class => [
            WebHookEventListener::class,
        ],
        ProductSetCreated::class => [
            WebHookEventListener::class,
        ],
        ProductSetDeleted::class => [
            WebHookEventListener::class,
        ],
        ProductSetUpdated::class => [
            WebHookEventListener::class,
        ],
        UserCreated::class => [
            WebHookEventListener::class,
        ],
        UserDeleted::class => [
            WebHookEventListener::class,
        ],
        UserUpdated::class => [
            WebHookEventListener::class,
        ],
    ];

    public function boot()
    {
        parent::boot();
        Payment::observe(PaymentObserver::class);
        Deposit::observe(DepositObserver::class);
    }
}
