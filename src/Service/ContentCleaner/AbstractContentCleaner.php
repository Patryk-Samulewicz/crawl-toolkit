<?php

namespace CrawlToolkit\Service\ContentCleaner;

abstract class AbstractContentCleaner implements ContentCleanerInterface
{
    protected string $content;
    private const int MAX_CONTENT_LENGTH = 1000000; // 1MB limit

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
     * Validates if the content is valid for processing.
     *
     * @return bool
     */
    abstract protected function validateContent(): bool;

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