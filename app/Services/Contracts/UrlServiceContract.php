<?php

namespace App\Services\Contracts;

interface UrlServiceContract
{
    /**
     * Returns URL with a normalized path.
     *
     * @param bool $stripFluff Whether to strip query and fragment portions of the url
     */
    public function normalizeUrl(string $url, bool $stripFluff = false): string;

    /**
     * Returns URL's with a normalized path in http and https schema.
     *
     * @param bool $stripFluff Whether to strip query and fragment portions of the url
     */
    public function equivalentNormalizedUrls(string $url, bool $stripFluff = false): array;

    /**
     * Returns URL with $path appended to original after '/'.
     */
    public function urlAppendPath(string $url, string $path): string;

    /**
     * Returns URL with path set to $path.
     */
    public function urlSetPath(string $url, string $path): string;
}
