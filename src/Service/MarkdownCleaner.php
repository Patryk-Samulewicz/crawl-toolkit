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

        // Istniejące czyszczenie
        $markdown = preg_replace('/!\[.*?\]\(.*?\)/', '', $markdown);
        $markdown = preg_replace('/\[(.*?)\]\(.*?\)/', '', $markdown);
        $markdown = preg_replace('/<img.*?>/', '', $markdown);
        $markdown = preg_replace('/^#{1,6}\s+/m', '', $markdown);
        $markdown = preg_replace('/(\*\*|__)(.*?)(\*\*|__)/m', '$2', $markdown);
        $markdown = preg_replace('/(\*|_)(.*?)(\*|_)/m', '$2', $markdown);
        $markdown = preg_replace('/``````/s', '', $markdown);
        $markdown = preg_replace('/`(.*?)`/m', '$1', $markdown);

        // Dodatkowe czyszczenie problematycznych znaków
        $markdown = preg_replace('/[\x00-\x1F\x7F]/', '', $markdown); // Usuń znaki kontrolne
        $markdown = str_replace('"', "'", $markdown); // Zamień podwójne cudzysłowy na pojedyncze
        $markdown = str_replace('\\', '\\\\', $markdown); // Escapuj ukośniki
        $markdown = str_replace('/', '\\/', $markdown); // Escapuj ukośniki w przód
        $markdown = preg_replace('/\n/', ' ', $markdown); // Zamień nowe linie na spacje
        $markdown = preg_replace('/\t/', ' ', $markdown); // Zamień tabulacje na spacje

        // Usuń inne potencjalnie problematyczne znaki markdown
        $markdown = preg_replace('/[{}[\]|<>]/', '', $markdown);

        // Reszta czyszczenia jak w oryginalnej funkcji
        $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);

        // Trim each line
        $lines = explode("\n", $markdown);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, fn($line) => strlen($line) > 50);
        $lines = array_values(array_unique($lines));

        $result = [];
        $i = 0;
        while ($i < count($lines)) {
            $line = $lines[$i];
            if (strlen($line) < 500 && isset($lines[$i + 1])) {
                $line .= ' ' . $lines[$i + 1];
                $i += 2;
            } else {
                $i++;
            }
            $result[] = $line;
        }

        return implode("\n", $result);
    }

    public function extractHeadings(): array
    {
        $headings = [];
        $lines = explode("\n", $this->markdown);

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
                    'tag' => 'h' . strlen($matches[1]), // Liczba znaków '#' określa poziom nagłówka
                    'text' => $text, // Treść nagłówka
                ];
            }
        }

        return $headings;
    }

    public static function cleanMarkdown(string $markdown): string {
        $cleaner = new self($markdown);
        return $cleaner->clean();
    }
}