<?php

namespace App\Services\Contracts;

interface UrlServiceContract
{
    /**
     * Returns URL with a normalized path
     *
     * @param string $url
     * @param bool $stripFluff Whether to strip query and fragment portions of the url
     */
    public function normalizeUrl(string $url, bool $stripFluff = false): string;

    /**
     * Returns URL's with a normalized path in http and https schema
     *
     * @param string $url
     * @param bool $stripFluff Whether to strip query and fragment portions of the url
     *
     * @return array
     */
    public function equivalentNormalizedUrls(string $url, bool $stripFluff = false): array;

    /**
     * Returns URL with $path appended to original after '/'
     *
     * @param string $url
     * @param string $path
     */
    public function urlAppendPath(string $url, string $path): string;

    /**
     * Returns URL with path set to $path
     *
     * @param string $url
     * @param string $path
     */
    public function urlSetPath(string $url, string $path): string;
}
