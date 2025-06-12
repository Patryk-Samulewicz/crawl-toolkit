<?php

namespace CrawlToolkit\Service\ContentParser;

use CrawlToolkit\Service\ContentCleaner\ContentCleanerInterface;

abstract class AbstractContentParser implements ContentParserInterface
{
    protected string $content;
    private const int MAX_CONTENT_LENGTH = 20000000; // 20MB limit

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(string $content)
    {
        if (empty($content)) {
            throw new \InvalidArgumentException('Content cannot be empty.');
        }
        if (strlen($content) > self::MAX_CONTENT_LENGTH) {
            throw new \InvalidArgumentException('Content exceeds maximum allowed length of ' . self::MAX_CONTENT_LENGTH . ' bytes.');
        }
        $this->content = $content;
    }

    /**
     * Returns the raw content.
     *
     * @return string
     */
    protected function getContent(): string
    {
        return $this->content;
    }
}