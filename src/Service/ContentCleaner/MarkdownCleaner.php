<?php

namespace CrawlToolkit\Service\ContentCleaner;

/**
 * Service class for cleaning and processing Markdown content.
 *
 * This class provides functionality to clean Markdown content by removing formatting,
 * images, links, and other elements while preserving the main text content.
 */
class MarkdownCleaner extends AbstractContentCleaner
{
    public function __construct(string $markdown)
    {
        parent::__construct($markdown);
    }

    protected function validateContent(): bool
    {
        return !empty($this->content);
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
        $markdown = $this->content;

        // Usuwanie obrazów i linków
        $markdown = preg_replace('/!\[.*?\]\(.*?\)/', '', $markdown);
        $markdown = preg_replace('/\[(.*?)\]\(.*?\)/', '', $markdown);
        $markdown = preg_replace('/<img.*?>/', '', $markdown);

        // Usuwanie formatowania
        $markdown = preg_replace('/^#{1,6}\s+/m', '', $markdown);
        $markdown = preg_replace('/(\*\*|__)(.*?)(\*\*|__)/m', '$2', $markdown);
        $markdown = preg_replace('/(\*|_)(.*?)(\*|_)/m', '$2', $markdown);
        $markdown = preg_replace('/``````/s', '', $markdown);
        $markdown = preg_replace('/`(.*?)`/m', '$1', $markdown);

        // Czyszczenie znaków
        $markdown = preg_replace('/[\x00-\x1F\x7F]/', '', $markdown);
        $markdown = str_replace('"', "'", $markdown);
        $markdown = str_replace('\\', '\\\\', $markdown);
        $markdown = str_replace('/', '\\/', $markdown);
        $markdown = preg_replace('/[{}[\]|<>]/', '', $markdown);

        // Normalizacja białych znaków
        $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);
        $markdown = preg_replace('/\t/', ' ', $markdown);

        // Przetwarzanie linii
        $lines = explode("\n", $markdown);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, fn($line) => !empty($line));
        $lines = array_values(array_unique($lines));

        // Łączenie krótkich linii
        $result = [];
        $i = 0;
        $maxIterations = count($lines);
        $iterations = 0;
        
        while ($i < count($lines) && $iterations < $maxIterations) {
            $line = $lines[$i];
            if (strlen($line) < 500 && isset($lines[$i + 1])) {
                // Dodajemy spację między liniami
                $line = rtrim($line, '. ') . '. ' . ltrim($lines[$i + 1]);
                $i += 2;
            } else {
                $i++;
            }
            $result[] = $line;
            $iterations++;
        }

        return implode("\n", $result);
    }

    public function extractHeadings(): array
    {
        $markdown = $this->content;
        $headings = [];
        $lines = explode("\n", $markdown);

        foreach ($lines as $line) {
            if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $matches)) {
                if (empty(trim($matches[2]))) {
                    continue;
                }

                $text = self::cleanMarkdown(trim($matches[2]));

                if (empty($text)) {
                    continue;
                }

                $headings[] = [
                    'tag' => 'h' . strlen($matches[1]),
                    'text' => $text,
                ];
            }
        }

        return $headings;
    }

    public static function cleanMarkdown(string $markdown): string
    {
        $cleaner = new self($markdown);
        return $cleaner->clean();
    }
}