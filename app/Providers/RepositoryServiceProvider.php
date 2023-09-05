<?php

namespace App\Providers;

use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\ProductRepository as EloquentProductRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    private const CONTRACTS = [
        ProductRepositoryContract::class => EloquentProductRepository::class,
    ];

    public function register(): void
    {
        foreach (self::CONTRACTS as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
