<?php

namespace App\Providers;

use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\DiscountRepository;
use App\Repositories\ProductRepository;
use App\Services\AnalyticsService;
use App\Services\AppService;
use App\Services\AuthService;
use App\Services\AvailabilityService;
use App\Services\Contracts\AnalyticsServiceContract;
use App\Services\Contracts\AppServiceContract;
use App\Services\Contracts\AuthServiceContract;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\DepositServiceContract;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\DocumentServiceContract;
use App\Services\Contracts\EventServiceContract;
use App\Services\Contracts\FavouriteServiceContract;
use App\Services\Contracts\GoogleCategoryServiceContract;
use App\Services\Contracts\ItemServiceContract;
use App\Services\Contracts\MediaAttachmentServiceContract;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\MetadataServiceContract;
use App\Services\Contracts\NameServiceContract;
use App\Services\Contracts\OneTimeSecurityCodeContract;
use App\Services\Contracts\OptionServiceContract;
use App\Services\Contracts\OrderServiceContract;
use App\Services\Contracts\PaymentMethodServiceContract;
use App\Services\Contracts\PaymentServiceContract;
use App\Services\Contracts\PermissionServiceContract;
use App\Services\Contracts\PriceServiceContract;
use App\Services\Contracts\ProductServiceContract;
use App\Services\Contracts\ProviderServiceContract;
use App\Services\Contracts\ReorderServiceContract;
use App\Services\Contracts\RoleServiceContract;
use App\Services\Contracts\SavedAddressServiceContract;
use App\Services\Contracts\SchemaCrudServiceContract;
use App\Services\Contracts\SchemaServiceContract;
use App\Services\Contracts\SettingsServiceContract;
use App\Services\Contracts\ShippingMethodServiceContract;
use App\Services\Contracts\ShippingTimeDateServiceContract;
use App\Services\Contracts\SilverboxServiceContract;
use App\Services\Contracts\SortServiceContract;
use App\Services\Contracts\StatusServiceContract;
use App\Services\Contracts\TokenServiceContract;
use App\Services\Contracts\TranslationServiceContract;
use App\Services\Contracts\UrlServiceContract;
use App\Services\Contracts\UserLoginAttemptServiceContract;
use App\Services\Contracts\UserServiceContract;
use App\Services\Contracts\WebHookServiceContract;
use App\Services\Contracts\WishlistServiceContract;
use App\Services\DepositService;
use App\Services\DiscountService;
use App\Services\DocumentService;
use App\Services\EventService;
use App\Services\FavouriteService;
use App\Services\GoogleCategoryService;
use App\Services\ItemService;
use App\Services\MediaAttachmentService;
use App\Services\MediaService;
use App\Services\MetadataService;
use App\Services\NameService;
use App\Services\OneTimeSecurityCodeService;
use App\Services\OptionService;
use App\Services\OrderService;
use App\Services\PaymentMethodService;
use App\Services\PaymentService;
use App\Services\PermissionService;
use App\Services\PriceService;
use App\Services\ProductService;
use App\Services\ProviderService;
use App\Services\ReorderService;
use App\Services\RoleService;
use App\Services\SavedAddressService;
use App\Services\SchemaCrudService;
use App\Services\SchemaService;
use App\Services\SettingsService;
use App\Services\ShippingMethodService;
use App\Services\ShippingTimeDateService;
use App\Services\SilverboxService;
use App\Services\SortService;
use App\Services\StatusService;
use App\Services\TokenService;
use App\Services\TranslationService;
use App\Services\UrlService;
use App\Services\UserLoginAttemptService;
use App\Services\UserService;
use App\Services\WebHookService;
use App\Services\WishlistService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;

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
        SchemaCrudServiceContract::class => SchemaCrudService::class,
        SettingsServiceContract::class => SettingsService::class,
        ShippingMethodServiceContract::class => ShippingMethodService::class,
        UserServiceContract::class => UserService::class,
        RoleServiceContract::class => RoleService::class,
        PermissionServiceContract::class => PermissionService::class,
        TokenServiceContract::class => TokenService::class,
        ProductServiceContract::class => ProductService::class,
        WebHookServiceContract::class => WebHookService::class,
        EventServiceContract::class => EventService::class,
        UrlServiceContract::class => UrlService::class,
        ItemServiceContract::class => ItemService::class,
        OneTimeSecurityCodeContract::class => OneTimeSecurityCodeService::class,
        TranslationServiceContract::class => TranslationService::class,
        SavedAddressServiceContract::class => SavedAddressService::class,
        AvailabilityServiceContract::class => AvailabilityService::class,
        DocumentServiceContract::class => DocumentService::class,
        MetadataServiceContract::class => MetadataService::class,
        SortServiceContract::class => SortService::class,
        UserLoginAttemptServiceContract::class => UserLoginAttemptService::class,
        StatusServiceContract::class => StatusService::class,
        DepositServiceContract::class => DepositService::class,
        ShippingTimeDateServiceContract::class => ShippingTimeDateService::class,
        ProviderServiceContract::class => ProviderService::class,
        GoogleCategoryServiceContract::class => GoogleCategoryService::class,
        WishlistServiceContract::class => WishlistService::class,
        FavouriteServiceContract::class => FavouriteService::class,
        PaymentMethodServiceContract::class => PaymentMethodService::class,
        PaymentServiceContract::class => PaymentService::class,
        PriceServiceContract::class => PriceService::class,
        MediaAttachmentServiceContract::class => MediaAttachmentService::class,
        SilverboxServiceContract::class => SilverboxService::class,

        // Repositories
        ProductRepositoryContract::class => ProductRepository::class,
        DiscountRepository::class => DiscountRepository::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        foreach (self::CONTRACTS as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }

        Factory::guessFactoryNamesUsing(
            /** @phpstan-ignore-next-line */
            fn (string $modelName) => 'Database\\Factories\\' . class_basename($modelName) . 'Factory',
        );

        /*
         * Local register of ide helper.
         * Needs to be full path.
         */
        if ($this->app->isLocal()) {
            $this->app->register('Barryvdh\\LaravelIdeHelper\\IdeHelperServiceProvider');
        }
    }
}
