<?php

namespace CrawlToolkit\Service\ContentParser;

use CrawlToolkit\Enum\ParserFormat;

interface ContentParserInterface
{
    public function parseToFormat(ParserFormat $format): string;
}