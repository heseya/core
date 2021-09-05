<?php

namespace Heseya\GraphQL;

use App\Enums\DiscountType;
use MLL\GraphQLPlayground\GraphQLPlaygroundServiceProvider;
use Nuwave\Lighthouse\LighthouseServiceProvider;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Schema\Types\LaravelEnumType;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/lighthouse.php', 'lighthouse');
    }

    public function boot(TypeRegistry $typeRegistry): void
    {
        $this->app->register(LighthouseServiceProvider::class);
        $typeRegistry->register(new LaravelEnumType(DiscountType::class));

        if (config('app.debug')) {
            $this->app->register(GraphQLPlaygroundServiceProvider::class);
        }
    }
}
