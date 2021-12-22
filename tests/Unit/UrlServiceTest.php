<?php

namespace Tests\Unit;


use App\Services\Contracts\UrlServiceContract;
use Tests\TestCase;

class UrlServiceTest extends TestCase
{
    private UrlServiceContract $urlService;

    public function setUp(): void
    {
        parent::setUp();

        $this->urlService = app(UrlServiceContract::class);
    }

    public function testUrlNormalization(): void
    {
        $this->assertEquals(
            $this->urlService->normalizeUrl('http://example.com/test'),
            $this->urlService->normalizeUrl('http://example.com/test/'),
        );
    }

    public function testUrlFluffRemoval(): void
    {
        $this->assertEquals(
            $this->urlService->normalizeUrl('http://example.com/test'),
            $this->urlService->normalizeUrl('http://example.com/test?fluff=true#more-fluff', true),
        );
    }

    public function testUrlEquivalents(): void
    {
        $this->assertEquals(
            [
                'http://example.com/path',
                'https://example.com/path',
            ],
            $this->urlService->equivalentNormalizedUrls('//example.com/path'),
        );
    }

    public function testUrlSetPath(): void
    {
        $this->assertEquals(
            'https://example.com/path?get=true#fragment',
            $this->urlService->urlSetPath(
                'https://example.com?get=true#fragment',
                'path/'
            ),
        );
    }

    public function testUrlAppendPath(): void
    {
        $this->assertEquals(
            'https://example.com/path/subpath?get=true#fragment',
            $this->urlService->urlAppendPath(
                'https://example.com/path?get=true#fragment',
                '/subpath/'
            ),
        );
    }

    public function testUrlAppendPathToNone(): void
    {
        $this->assertEquals(
            'https://example.com/path?get=true#fragment',
            $this->urlService->urlAppendPath(
                'https://example.com?get=true#fragment',
                '/path/'
            ),
        );
    }
}
