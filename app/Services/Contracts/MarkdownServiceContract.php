<?php

namespace App\Services\Contracts;

interface MarkdownServiceContract
{
    public function fromHtml(string $html): string;
}
