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
        if (!is_string($markdown) || empty($markdown)) {
            return '';
        }

        // Usuwanie sekcji HTML
        $markdown = preg_replace('/<head.*?>.*?<\/head>/is', '', $markdown);
        $markdown = preg_replace('/<script.*?>.*?<\/script>/is', '', $markdown);
        $markdown = preg_replace('/<style.*?>.*?<\/style>/is', '', $markdown);
        $markdown = preg_replace('/<nav.*?>.*?<\/nav>/is', '', $markdown);
        $markdown = preg_replace('/<footer.*?>.*?<\/footer>/is', '', $markdown);
        $markdown = preg_replace('/<header.*?>.*?<\/header>/is', '', $markdown);
        $markdown = preg_replace('/<aside.*?>.*?<\/aside>/is', '', $markdown);

        // Usuwanie komentarzy HTML
        $markdown = is_string($markdown) ? preg_replace('/<!--.*?-->/s', '', $markdown) : '';
        if (!is_string($markdown)) return '';

        // Usuwanie innych tagów HTML z zachowaniem ich zawartości
        $markdown = preg_replace('/<[^>]*script.*?>.*?<\/script>/is', '', $markdown);
        $markdown = preg_replace('/<div[^>]*class=["\'](?:.*?(?:menu|sidebar|widget|footer|header|navigation|cookie|popup|banner|ad).*?)["\'][^>]*>.*?<\/div>/is', '', $markdown);
        $markdown = preg_replace('/<svg.*?<\/svg>/is', '', $markdown);

        // Usuwanie atrybutów z pozostałych tagów
        $markdown = preg_replace('/<([a-z][a-z0-9]*)[^>]*>/is', '<$1>', $markdown);

        // Zamiana podstawowych tagów HTML na ich tekstową reprezentację lub usunięcie
        $markdown = preg_replace('/<\/?(?:p|div|span|section|article)[^>]*>/is', "\n", $markdown);
        $markdown = preg_replace('/<br\s*\/?>/i', "\n", $markdown);
        $markdown = preg_replace('/<hr\s*\/?>/i', "---\n", $markdown);
        $markdown = preg_replace('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', "\n$2\n", $markdown);

        // Usuwanie pozostałych tagów HTML
        $markdown = preg_replace('/<[^>]+>/', '', $markdown);

        // Dekodowanie encji HTML
        $markdown = html_entity_decode($markdown, ENT_QUOTES | ENT_HTML5);

        // Usuwanie obrazów i linków w markdown - kolejność jest ważna
        $markdown = preg_replace('/!\[.*?\]\(.*?\)/', '', $markdown); // Najpierw usuwamy obrazy
        $markdown = preg_replace('/\[.*?\]\(.*?\)/', '', $markdown); // Potem usuwamy linki (całkowicie)

        // Usuwanie formatowania
        $markdown = preg_replace('/^#{1,6}\s+/m', '', $markdown);
        $markdown = preg_replace('/(\*\*|__)(.*?)(\*\*|__)/m', '$2', $markdown);
        $markdown = preg_replace('/(\*|_)(.*?)(\*|_)/m', '$2', $markdown);
        $markdown = preg_replace('/```.*?```/s', '', $markdown);
        $markdown = preg_replace('/`(.*?)`/m', '$1', $markdown);

        // Usuwanie wielu powtórzeń wykrzyknika
        $markdown = preg_replace('/!{2,}/', '', $markdown);

        // Czyszczenie znaków
        $markdown = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $markdown);
        $markdown = str_replace('"', "'", $markdown);
        $markdown = preg_replace('/\\\+/', ' ', $markdown);

        // Normalizacja białych znaków
        $markdown = preg_replace('/\s+/', ' ', $markdown);
        $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);

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