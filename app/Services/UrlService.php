<?php

namespace App\Services;

use App\Services\Contracts\UrlServiceContract;
use GuzzleHttp\Psr7\Uri;

class UrlService implements UrlServiceContract
{
    public function normalizeUrl(string $url, bool $stripFluff = false): string
    {
        $uri = new Uri($url);

        return $this->toNormalizedUrl($uri, $stripFluff);
    }

    public function equivalentNormalizedUrls(string $url, bool $stripFluff = false): array
    {
        $uri = $this->toNormalizedUri($url, $stripFluff);

        return [
            $uri->withScheme('http')->__toString(),
            $uri->withScheme('https')->__toString(),
        ];
    }

    public function urlSetPath(string $url, string $path): string
    {
        $uri = new Uri($url);

        return $this->toNormalizedUrl(
            $uri->withPath('/' . ltrim($path, '/')),
            false,
        );
    }

    public function urlAppendPath(string $url, string $path): string
    {
        $uri = $this->toNormalizedUri($url, false);

        $path = $uri->getPath() . '/' . ltrim($path, '/');

        return $this->toNormalizedUrl(
            $uri->withPath($path),
            false,
        );
    }

    private function toNormalizedUri(string $url, bool $stripFluff): Uri
    {
        $uri = new Uri($url);

        return $this->normalizeUri($uri, $stripFluff);
    }

    private function toNormalizedUrl(Uri $uri, bool $stripFluff): string
    {
        return $this->normalizeUri($uri, $stripFluff)->__toString();
    }

    private function normalizeUri(Uri $uri, bool $stripFluff): Uri
    {
        if ($stripFluff) {
            $uri = $this->stripUriQueryAndFragment($uri);
        }

        return $this->normalizeUriPath($uri);
    }

    private function normalizeUriPath(Uri $uri): Uri
    {
        $path = rtrim($uri->getPath(), '/');

        return $uri->withPath($path);
    }

    private function stripUriQueryAndFragment(Uri $uri): Uri
    {
        return $uri
            ->withQuery('')
            ->withFragment('');
    }
}
