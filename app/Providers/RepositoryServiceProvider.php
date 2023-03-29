<?php

namespace App\Providers;

use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\Elastic\ProductRepository as ElasticProductRepository;
use App\Repositories\Eloquent\ProductRepository as EloquentProductRepository;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    private const ELOQUENT = [
        ProductRepositoryContract::class => EloquentProductRepository::class,
    ];

    private const ELASTIC = [
        ProductRepositoryContract::class => ElasticProductRepository::class,
    ];

    public function register(): void
    {
        $forceDatabase = request()->boolean('force_database_search');

        $contracts = match ($forceDatabase ? 'database' : Config::get('scout.driver')) {
            'elastic' => self::ELASTIC,
            'database' => self::ELOQUENT,
            default => throw new Exception('Invalid scout driver "' . Config::get('scout.driver') . '"'),
        };

        foreach ($contracts as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
