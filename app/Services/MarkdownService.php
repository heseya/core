<?php

namespace App\Services;

use App\Services\Contracts\MarkdownServiceContract;
use League\HTMLToMarkdown\HtmlConverter;

class MarkdownService implements MarkdownServiceContract
{
    protected HtmlConverter $htmlConverter;

    public function __construct()
    {
        $this->htmlConverter = new HtmlConverter(['strip_tags' => true]);
    }

    public function toHtml(string $markdown): string
    {
        return parsedown($markdown);
    }

    public function fromHtml(string $html): string
    {
        return $this->htmlConverter->convert($html);
    }
}
