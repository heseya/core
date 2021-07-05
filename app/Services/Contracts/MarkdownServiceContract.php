<?php

namespace App\Services\Contracts;

interface MarkdownServiceContract
{
    public function toHtml(string $markdown): string;

    public function fromHtml(string $html): string;
}
