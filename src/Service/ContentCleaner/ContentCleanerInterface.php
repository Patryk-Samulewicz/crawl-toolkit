<?php

namespace CrawlToolkit\Service\ContentCleaner;

interface ContentCleanerInterface
{
    /**
     * Cleans the content and returns the processed result.
     *
     * @return string Cleaned content
     */
    public function clean(): string;

    /**
     * Extracts headings from the content.
     *
     * @return array<array{tag: string, text: string}> Array of headings with their tags and text content
     */
    public function extractHeadings(): array;
} 