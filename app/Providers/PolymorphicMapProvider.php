<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class PolymorphicMapProvider extends ServiceProvider
{
    public function boot(): void
    {
        Relation::enforceMorphMap(Config::get('relation-aliases'));
    }
}
