<?php

namespace Tests\Unit;

use App\Http\Middleware\UndotParams;
use Illuminate\Http\Request;
use Tests\TestCase;

class UndotParamsTest extends TestCase
{
    public function testUndotParamsMiddleware(): void
    {
        $request = Request::create('/orders?metadata.Producent=Heseya', 'GET');

        $middleware = new UndotParams();

        $middleware->handle($request, function ($req): void {
            $this->assertIsArray($req->metadata);
            $this->assertEquals(['Producent' => 'Heseya'], $req->metadata);
        });
    }

    public function testUndotParamsMiddlewareNested(): void
    {
        $request = Request::create('/orders?metadata.Producent.Zagraniczny=false', 'GET');

        $middleware = new UndotParams();

        $middleware->handle($request, function ($req): void {
            $this->assertIsArray($req->metadata);
            $this->assertEquals(
                [
                    'Producent' => [
                        'Zagraniczny' => 'false',
                    ],
                ],
                $req->metadata
            );
        });
    }

    public function testUndotParamsMiddlewareMixed(): void
    {
        $request = Request::create(implode('', [
            '/orders?',
            'metadata.Producent=Heseya',
            '&metadata.Kolor=Czerwony',
            '&metadata.Kraj.Produkcja=Chiny',
            '&metadata.Kraj.Dystrybucja[]=Polska',
            '&metadata.Kraj.Dystrybucja[]=Francja',
            '&not_related_param=test',
        ]));

        $middleware = new UndotParams();

        $middleware->handle($request, function ($req): void {
            $this->assertIsArray($req->metadata);
            $this->assertEquals(
                [
                    'Producent' => 'Heseya',
                    'Kolor' => 'Czerwony',
                    'Kraj' => [
                        'Produkcja' => 'Chiny',
                        'Dystrybucja' => ['Polska', 'Francja'],
                    ],
                ],
                $req->metadata
            );
            $this->assertEquals('test', $req->not_related_param);
            $this->assertNotEquals('Heseya', $req->metadata_Producent);
        });
    }
}
