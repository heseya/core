<?php

namespace App\Http;

use App\Http\Middleware\AcceptLanguage;
use App\Http\Middleware\AppAccessRestrict;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\ContentLanguage;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\UndotParams;
use App\Http\Middleware\UserAccessRestrict;
use Heseya\Pagination\Http\Middleware\Pagination;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Spatie\Permission\Middlewares\PermissionMiddleware;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, string>
     */
    protected $middleware = [
        HandleCors::class,
        ValidatePostSize::class,
        AcceptLanguage::class,
        TrustProxies::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        Pagination::class,
        Authenticate::class,
        ContentLanguage::class,
        UndotParams::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * This is the only middleware that should be here,
     * because for some reason it doesn't want to work as it is in the main array.
     *
     * @var array<string, array<int, string>>
     */
    protected $middlewareGroups = [
        'api' => [
            SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string>
     */
    protected $middlewareAliases = [
        'can' => Authorize::class,
        'throttle' => ThrottleRequests::class,
        'cache.headers' => SetCacheHeaders::class,
        'permission' => PermissionMiddleware::class,
        'app.restrict' => AppAccessRestrict::class,
        'user.restrict' => UserAccessRestrict::class,
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var array<class-string>
     */
    protected $middlewarePriority = [
        HandleCors::class,
        UndotParams::class,
        SubstituteBindings::class,
        Authenticate::class,
        AppAccessRestrict::class,
        Authorize::class,
    ];
}
