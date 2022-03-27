<?php

namespace App\Providers;

use App\Services\AnalyticsService;
use App\Services\AppService;
use App\Services\AttributeOptionService;
use App\Services\AttributeService;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\AvailabilityService;
use App\Services\Contracts\AnalyticsServiceContract;
use App\Services\Contracts\AppServiceContract;
use App\Services\Contracts\AttributeOptionServiceContract;
use App\Services\Contracts\AttributeServiceContract;
use App\Services\Contracts\AuditServiceContract;
use App\Services\Contracts\AuthServiceContract;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\EventServiceContract;
use App\Services\Contracts\ItemServiceContract;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\NameServiceContract;
use App\Services\Contracts\OneTimeSecurityCodeContract;
use App\Services\Contracts\OptionServiceContract;
use App\Services\Contracts\OrderServiceContract;
use App\Services\Contracts\PageServiceContract;
use App\Services\Contracts\PermissionServiceContract;
use App\Services\Contracts\ProductSearchServiceContract;
use App\Services\Contracts\ProductServiceContract;
use App\Services\Contracts\ProductSetServiceContract;
use App\Services\Contracts\ReorderServiceContract;
use App\Services\Contracts\RoleServiceContract;
use App\Services\Contracts\SchemaServiceContract;
use App\Services\Contracts\SeoMetadataServiceContract;
use App\Services\Contracts\SettingsServiceContract;
use App\Services\Contracts\ShippingMethodServiceContract;
use App\Services\Contracts\SortServiceContract;
use App\Services\Contracts\TokenServiceContract;
use App\Services\Contracts\UrlServiceContract;
use App\Services\Contracts\UserServiceContract;
use App\Services\Contracts\WebHookServiceContract;
use App\Services\DiscountService;
use App\Services\EventService;
use App\Services\ItemService;
use App\Services\MediaService;
use App\Services\NameService;
use App\Services\OneTimeSecurityCodeService;
use App\Services\OptionService;
use App\Services\OrderService;
use App\Services\PageService;
use App\Services\PermissionService;
use App\Services\ProductSearchService;
use App\Services\ProductService;
use App\Services\ProductSetService;
use App\Services\ReorderService;
use App\Services\RoleService;
use App\Services\SchemaService;
use App\Services\SeoMetadataService;
use App\Services\SettingsService;
use App\Services\ShippingMethodService;
use App\Services\SortService;
use App\Services\TokenService;
use App\Services\UrlService;
use App\Services\UserService;
use App\Services\WebHookService;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;

class AppServiceProvider extends ServiceProvider
{
    private const CONTRACTS = [
        AuthServiceContract::class => AuthService::class,
        AnalyticsServiceContract::class => AnalyticsService::class,
        AppServiceContract::class => AppService::class,
        DiscountServiceContract::class => DiscountService::class,
        ReorderServiceContract::class => ReorderService::class,
        NameServiceContract::class => NameService::class,
        MediaServiceContract::class => MediaService::class,
        OptionServiceContract::class => OptionService::class,
        OrderServiceContract::class => OrderService::class,
        SchemaServiceContract::class => SchemaService::class,
        SettingsServiceContract::class => SettingsService::class,
        PageServiceContract::class => PageService::class,
        ShippingMethodServiceContract::class => ShippingMethodService::class,
        ProductSetServiceContract::class => ProductSetService::class,
        UserServiceContract::class => UserService::class,
        RoleServiceContract::class => RoleService::class,
        PermissionServiceContract::class => PermissionService::class,
        AuditServiceContract::class => AuditService::class,
        TokenServiceContract::class => TokenService::class,
        ProductServiceContract::class => ProductService::class,
        WebHookServiceContract::class => WebHookService::class,
        EventServiceContract::class => EventService::class,
        SeoMetadataServiceContract::class => SeoMetadataService::class,
        UrlServiceContract::class => UrlService::class,
        ItemServiceContract::class => ItemService::class,
        OneTimeSecurityCodeContract::class => OneTimeSecurityCodeService::class,
        AvailabilityServiceContract::class => AvailabilityService::class,
        AttributeServiceContract::class => AttributeService::class,
        AttributeOptionServiceContract::class => AttributeOptionService::class,
        SortServiceContract::class => SortService::class,
        ProductSearchServiceContract::class => ProductSearchService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        foreach (self::CONTRACTS as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }

        /**
         * Local register of ide helper.
         * Needs to be full path.
         */
        if ($this->app->isLocal()) {
            $this->app->register('\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider');
        }
    }

    public function boot(): void
    {
        Builder::macro('sort', function (?string $sortString = null) {
            if ($sortString !== null) {
                return app(SortServiceContract::class)->sort($this, $sortString);
            }

            return $this;
        });
    }
}
