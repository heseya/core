<?php

namespace Heseya\Pagination\Tests\Unit;

use App\Exceptions\StoreException;
use Heseya\Pagination\Http\Middleware\Pagination;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Tests\CreatesApplication;

class MiddlewareTest extends TestCase
{
    use CreatesApplication;

    public function testNoLimit()
    {
        $request = Request::create('/admin', 'GET');

        $middleware = new Pagination;
        $middleware->handle($request, function () {});

        $this->assertEquals(config('pagination.per_page'), 100);
    }

    public function testLimit()
    {
        $request = Request::create('/admin?limit=50', 'GET');

        $middleware = new Pagination;
        $middleware->handle($request, function () {});

        $this->assertEquals(config('pagination.per_page'), 50);
    }

    public function testLimitMax()
    {
        $request = Request::create('/admin?limit=1000', 'GET');

        $middleware = new Pagination;

        $this->expectException(StoreException::class);
        $middleware->handle($request, function () {});
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pagination.per_page' => 100,
            'pagination.max' => 500,
        ]);
    }
}
