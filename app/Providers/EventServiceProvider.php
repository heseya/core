<?php

namespace App\Providers;

use App\Events\CouponCreated;
use App\Events\CouponDeleted;
use App\Events\CouponUpdated;
use App\Events\FailedLoginAttempt;
use App\Events\ItemCreated;
use App\Events\ItemDeleted;
use App\Events\ItemUpdated;
use App\Events\ItemUpdatedQuantity;
use App\Events\NewLocalizationLoginAttempt;
use App\Events\OrderCreated;
use App\Events\OrderDocumentEvent;
use App\Events\OrderRequestedShipping;
use App\Events\OrderUpdated;
use App\Events\OrderUpdatedPaid;
use App\Events\OrderUpdatedShippingNumber;
use App\Events\OrderUpdatedStatus;
use App\Events\PageCreated;
use App\Events\PageDeleted;
use App\Events\PageUpdated;
use App\Events\PasswordReset;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductPriceUpdated;
use App\Events\ProductSetCreated;
use App\Events\ProductSetDeleted;
use App\Events\ProductSetUpdated;
use App\Events\ProductUpdated;
use App\Events\SaleCreated;
use App\Events\SaleDeleted;
use App\Events\SaleUpdated;
use App\Events\SendOrderDocument;
use App\Events\SendOrderUrls;
use App\Events\SuccessfulLoginAttempt;
use App\Events\TfaInit;
use App\Events\TfaRecoveryCodesChanged;
use App\Events\TfaSecurityCode;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Listeners\ItemUpdatedQuantityListener;
use App\Listeners\OrderCreatedListener;
use App\Listeners\OrderUpdatedStatusListener;
use App\Listeners\UserCreatedListener;
use App\Listeners\WebHookEventListener;
use App\Listeners\WebHookFailedListener;
use App\Models\AttributeOption;
use App\Models\Deposit;
use App\Models\ItemProduct;
use App\Models\Payment;
use App\Models\Schema;
use App\Observers\AttributeOptionObserver;
use App\Observers\DepositObserver;
use App\Observers\ItemProductObserver;
use App\Observers\PaymentObserver;
use App\Observers\SchemaObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Apple\AppleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        OrderCreated::class => [
            OrderCreatedListener::class,
        ],
        OrderUpdatedStatus::class => [
            OrderUpdatedStatusListener::class,
        ],
        FinalWebhookCallFailedEvent::class => [
            WebHookFailedListener::class,
        ],
        ItemUpdatedQuantity::class => [
            ItemUpdatedQuantityListener::class,
        ],
        UserCreated::class => [
            UserCreatedListener::class,
        ],
    ];

    /** @var array<class-string> */
    private array $webhookEvents = [
        CouponCreated::class,
        CouponDeleted::class,
        CouponUpdated::class,
        FailedLoginAttempt::class,
        ItemCreated::class,
        ItemDeleted::class,
        ItemUpdated::class,
        ItemUpdatedQuantity::class,
        NewLocalizationLoginAttempt::class,
        OrderCreated::class,
        OrderDocumentEvent::class,
        OrderRequestedShipping::class,
        OrderUpdated::class,
        OrderUpdatedPaid::class,
        OrderUpdatedShippingNumber::class,
        OrderUpdatedStatus::class,
        SendOrderUrls::class,
        PageCreated::class,
        PageDeleted::class,
        PageUpdated::class,
        PasswordReset::class,
        ProductCreated::class,
        ProductDeleted::class,
        ProductSetCreated::class,
        ProductSetDeleted::class,
        ProductSetUpdated::class,
        ProductUpdated::class,
        ProductPriceUpdated::class,
        SaleCreated::class,
        SaleDeleted::class,
        SaleUpdated::class,
        SendOrderDocument::class,
        SuccessfulLoginAttempt::class,
        TfaInit::class,
        TfaRecoveryCodesChanged::class,
        TfaSecurityCode::class,
        UserCreated::class,
        UserDeleted::class,
        UserUpdated::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Event::listen($this->webhookEvents, WebHookEventListener::class);
        Event::listen(SocialiteWasCalled::class, AppleExtendSocialite::class);

        // Ugly observers 🤮
        AttributeOption::observe(AttributeOptionObserver::class);
        Deposit::observe(DepositObserver::class);
        ItemProduct::observe(ItemProductObserver::class);
        Payment::observe(PaymentObserver::class);
        Schema::observe(SchemaObserver::class);
    }
}
