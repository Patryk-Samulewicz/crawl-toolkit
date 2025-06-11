<?php

namespace CrawlToolkit\Service\ContentCleaner;

/**
 * Service class for cleaning and processing HTML content using regex.
 */
class HtmlCleaner extends AbstractContentCleaner
{
    private int $maxProcessingTime = 5; // mniejszy limit czasu, bo regex powinien być szybszy
    private array $extractedHeadings = [];

    public function __construct(string $html)
    {
        parent::__construct($html);
    }

    protected function validateContent(): bool
    {
        return !empty($this->content);
    }

    /**
     * Cleans the HTML content using regular expressions.
     *
     * @return string Cleaned HTML content
     */
    public function clean(): string
    {
        $startTime = microtime(true);

        // Zapisz nagłówki przed usunięciem atrybutów (dla metody extractHeadings)
        $this->extractedHeadings = $this->extractHeadingsWithRegex();

        // 1. Usuń komentarze
        $this->content = preg_replace('/<!--.*?-->/s', '', $this->content);

        // 2. Usuń skrypty i style (kompletne bloki)
        $this->content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $this->content);
        $this->content = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $this->content);

        // 3. Usuń inne niechciane elementy
        $tags = ['img', 'svg', 'path', 'picture', 'source', 'video', 'audio', 'iframe', 'canvas', 'noscript', 'object', 'embed'];
        foreach ($tags as $tag) {
            if (microtime(true) - $startTime > $this->maxProcessingTime) break;
            $this->content = preg_replace("/<{$tag}[^>]*>.*?<\/{$tag}>/is", '', $this->content);
            $this->content = preg_replace("/<{$tag}[^>]*\/?>/is", '', $this->content);
        }

        // 4. Usuń wszystkie atrybuty ze wszystkich tagów
        $this->content = preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/is', '<$1>', $this->content);

        // 5. Usuń puste tagi
        for ($i = 0; $i < 2; $i++) { // Ograniczona liczba iteracji
            if (microtime(true) - $startTime > $this->maxProcessingTime) break;
            $prevContent = $this->content;
            $this->content = preg_replace('/<([a-z][a-z0-9]*(?:\:[a-z][a-z0-9]*)?)>\s*<\/\\1>/is', '', $this->content);
            if ($prevContent === $this->content) break; // Jeśli nie było zmian, przerwij
        }

        // 6. Usuń zbędne przestrzenie
        $this->content = preg_replace('/\s{2,}/s', ' ', $this->content);
        $this->content = trim($this->content);

        // Zachowaj tylko najważniejsze tagi w przypadku bardzo dużych tekstów
        if (strlen($this->content) > 1000000) { // ~1MB
            $this->content = $this->getImportantContent();
        }

        return $this->content;
    }

    /**
     * Extracts only important content when HTML is too large
     */
    private function getImportantContent(): string
    {
        $result = '';

        // Wyodrębnienie głównej treści (body lub article)
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $this->content, $matches)) {
            $content = $matches[1];
        } else {
            $content = $this->content;
        }

        // Wyciągnij tylko paragrafy, nagłówki i listy
        preg_match_all('/<(h[1-6]|p|ul|ol|li)[^>]*>.*?<\/\1>/is', $content, $matches);
        if (!empty($matches[0])) {
            $result = implode("\n", $matches[0]);
        }

        return $result ?: $this->content;
    }

    /**
     * Extracts all headings (h1-h6) from the HTML content.
     *
     * @return array<array{tag: string, text: string}> Array of headings
     */
    public function extractHeadings(): array
    {
        if (!empty($this->extractedHeadings)) {
            return $this->extractedHeadings;
        }

        return $this->extractHeadingsWithRegex();
    }

    private function extractHeadingsWithRegex(): array
    {
        $headings = [];
        $pattern = '/<(h[1-6])[^>]*>(.*?)<\/\1>/is';

        if (preg_match_all($pattern, $this->content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tag = strtolower($match[1]);
                $text = trim(strip_tags($match[2]));

                if (!empty($text)) {
                    $headings[] = [
                        'tag' => $tag,
                        'text' => $text,
                    ];
                }
            }
        }

        return $headings;
    }

    /**
     * Ustawia maksymalny czas przetwarzania w sekundach.
     */
    public function setMaxProcessingTime(int $seconds): self
    {
        $this->maxProcessingTime = $seconds;
        return $this;
    }
}