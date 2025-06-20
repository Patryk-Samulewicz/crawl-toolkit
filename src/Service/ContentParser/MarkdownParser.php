<?php

namespace CrawlToolkit\Service\ContentParser;

use CrawlToolkit\Enum\ParserFormat;
use Parsedown;

class MarkdownParser extends AbstractContentParser
{
    public function __construct($markdownContent)
    {
        parent::__construct($markdownContent);
    }

    public function parseToFormat(ParserFormat $format): string
    {
        if ($format === ParserFormat::Markdown) {
            throw new \InvalidArgumentException("Markdown content cannot be parsed to HTML format.");
        }

        if ($format === ParserFormat::Html) {
            $parsedown = new Parsedown();
            return $parsedown->text($this->getContent());
        }

        throw new \InvalidArgumentException("Nieobsługiwany format: " . $format->value);
    }
}