<?php

namespace CrawlToolkit\Service\ContentParser;

use CrawlToolkit\Enum\ParserFormat;
use League\HTMLToMarkdown\HtmlConverter;


class HtmlParser extends AbstractContentParser
{
    public function __construct($htmlContent)
    {
        parent::__construct($htmlContent);
    }

    public function parseToFormat(ParserFormat $format): string
    {
        if ($format === ParserFormat::Html) {
            throw new \InvalidArgumentException("HTML content cannot be parsed to HTML format.");
        }

        if ($format === ParserFormat::Markdown) {
            $converter = new HtmlConverter();
            return $converter->convert($this->getContent());
        }

        throw new \InvalidArgumentException("Unsupported format: " . $format->value);
    }
}