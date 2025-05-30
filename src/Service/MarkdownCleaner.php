<?php

namespace CrawlToolkit\Service;

/**
 * Service class for cleaning and processing Markdown content.
 *
 * This class provides functionality to clean Markdown content by removing formatting,
 * images, links, and other elements while preserving the main text content.
 */
readonly class MarkdownCleaner
{
    private string $markdown;

    public function __construct(string $markdown) {
        if (empty($markdown)) {
            throw new \InvalidArgumentException('Markdown content cannot be empty.');
        }
        $this->markdown = $markdown;
    }

    /**
     * Cleans Markdown content by removing various formatting elements and optimizing text structure.
     *
     * The cleaning process includes:
     * - Removing images and links
     * - Removing heading symbols
     * - Removing bold/italic formatting
     * - Removing code blocks
     * - Cleaning up line breaks
     * - Merging short lines
     * - Removing duplicates
     *
     * @return string Cleaned text content
     */
    public function clean(): string
    {
        $markdown = $this->markdown;

        // Remove images ![alt](url)
        $markdown = preg_replace('/!\[.*?\]\(.*?\)/', '', $markdown);

        // Remove links [text](url)
        $markdown = preg_replace('/\[(.*?)\]\(.*?\)/', '', $markdown);

        // Remove HTML-style image tags
        $markdown = preg_replace('/<img.*?>/', '', $markdown);

        // Remove heading symbols (# and ##)
        $markdown = preg_replace('/^#{1,6}\s+/m', '', $markdown);

        // Remove bold/italic formatting (**, __, *, _)
        $markdown = preg_replace('/(\*\*|__)(.*?)(\*\*|__)/m', '$2', $markdown);
        $markdown = preg_replace('/(\*|_)(.*?)(\*|_)/m', '$2', $markdown);

        // Remove code blocks and inline code
        $markdown = preg_replace('/```.*?```/s', '', $markdown);
        $markdown = preg_replace('/`(.*?)`/m', '$1', $markdown);

        // Remove backslash escapes
        $markdown = str_replace('\\', '', $markdown);

        // Clean up multiple consecutive line breaks
        $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);

        // Trim each line
        $lines = explode("\n", $markdown);
        $lines = array_map('trim', $lines);
        // Filter lines - each line must have at least 50 characters
        $lines = array_filter($lines, fn($line) => strlen($line) > 50);

        // Remove duplicates
        $lines = array_values(array_unique($lines));

        // if line is less than 300 characters, append next line and remove next line
        $result = [];
        $i = 0;
        while ($i < count($lines)) {
            $line = $lines[$i];

            // Try to merge with next line if current is short
            if (strlen($line) < 500 && isset($lines[$i + 1])) {
                $line .= ' ' . $lines[$i + 1];
                $i += 2; // Skip the next line since we merged it
            } else {
                $i++;
            }

            $result[] = $line;
        }

        // Join the cleaned lines back into a single string
        return implode("\n", $result);
    }
}