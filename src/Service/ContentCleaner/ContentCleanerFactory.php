<?php

namespace CrawlToolkit\Service\ContentCleaner;

use CrawlToolkit\Enum\FetchType;

class ContentCleanerFactory
{
    public function create(FetchType $type, string $content): ContentCleanerInterface
    {
        return match ($type) {
            FetchType::Html => new HtmlCleaner($content),
            FetchType::Markdown => new MarkdownCleaner($content),
        };
    }
}