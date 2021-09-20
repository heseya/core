<?php

namespace Heseya\Pagination;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pagination.php',
            'pagination',
        );
    }
}
