<?php

namespace Heseya\Pagination\Tests\Unit;

use App\Exceptions\StoreException;
use Heseya\Pagination\Http\Middleware\Pagination;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\CreatesApplication;

class MiddlewareTest extends TestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('pagination.per_page', 100);
        Config::set('pagination.max', 500);
    }

    public function testNoLimit(): void
    {
        $request = Request::create('/admin', 'GET');

        $middleware = new Pagination();
        $middleware->handle($request, function (): void {
        });

        $this->assertEquals(config('pagination.per_page'), 100);
    }

    public function testLimit(): void
    {
        $request = Request::create('/admin?limit=50', 'GET');

        $middleware = new Pagination();
        $middleware->handle($request, function (): void {
        });

        $this->assertEquals(config('pagination.per_page'), 50);
    }

    public function testLimitValidation(): void
    {
        $request = Request::create('/admin?limit=TEST', 'GET');

        $middleware = new Pagination();

        $this->expectException(StoreException::class);
        $middleware->handle($request, function (): void {
        });
    }

    public function testLimitMax(): void
    {
        $request = Request::create('/admin?limit=1000', 'GET');

        $middleware = new Pagination();

        $this->expectException(StoreException::class);
        $middleware->handle($request, function (): void {
        });
    }

    public function testLimitMin(): void
    {
        $request = Request::create('/admin?limit=-1', 'GET');

        $middleware = new Pagination();

        $this->expectException(StoreException::class);
        $middleware->handle($request, function (): void {
        });
    }
}
