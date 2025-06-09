<?php

namespace CrawlToolkit\Service\ContentCleaner;

abstract class AbstractContentCleaner implements ContentCleanerInterface
{
    protected string $content;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(string $content)
    {
        if (empty($content)) {
            throw new \InvalidArgumentException('Content cannot be empty.');
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