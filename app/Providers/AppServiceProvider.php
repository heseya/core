<?php

namespace App\Providers;

use App\Services\AnalyticsService;
use App\Services\AppService;
use App\Services\Contracts\AnalyticsServiceContract;
use App\Services\Contracts\AppServiceContract;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\MarkdownServiceContract;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\NameServiceContract;
use App\Services\Contracts\OptionServiceContract;
use App\Services\Contracts\OrderServiceContract;
use App\Services\Contracts\PageServiceContract;
use App\Services\Contracts\ProductSetServiceContract;
use App\Services\Contracts\ReorderServiceContract;
use App\Services\Contracts\SchemaServiceContract;
use App\Services\Contracts\SettingsServiceContract;
use App\Services\DiscountService;
use App\Services\MarkdownService;
use App\Services\MediaService;
use App\Services\NameService;
use App\Services\OptionService;
use App\Services\OrderService;
use App\Services\PageService;
use App\Services\ProductSetService;
use App\Services\ReorderService;
use App\Services\SchemaService;
use App\Services\SettingsService;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const CONTRACTS = [
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
        MarkdownServiceContract::class => MarkdownService::class,
        PageServiceContract::class => PageService::class,
        ProductSetServiceContract::class => ProductSetService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        foreach (self::CONTRACTS as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }

        if ($this->app->isLocal()) {
            $this->app->register(IdeHelperServiceProvider::class);
        }
    }
}
