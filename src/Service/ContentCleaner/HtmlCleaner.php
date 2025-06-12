<?php

namespace CrawlToolkit\Service\ContentCleaner;

/**
 * Service class for cleaning and processing HTML content using regex.
 */
class HtmlCleaner extends AbstractContentCleaner
{
    private int $maxProcessingTime = 5;
    private array $extractedHeadings = [];
    private int $maxChunkSize = 500000; // wielkość fragmentu dla dużych dokumentów

    public function __construct(string $html)
    {
        parent::__construct($html);
        // Zwiększ limit rekursji PCRE jeśli to możliwe
        if (ini_get('pcre.recursion_limit') < 10000) {
            @ini_set('pcre.recursion_limit', '10000');
        }
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
        $this->extractedHeadings = $this->extractHeadingsWithRegex();

        // Dla bardzo dużych dokumentów, przetwarzaj je fragmentami
        if (strlen($this->content) > $this->maxChunkSize * 2) {
            return $this->processLargeHtml($startTime);
        }

        // Standardowe przetwarzanie z zabezpieczeniem przed null
        $this->content = $this->safeRegexReplace('/<!--.*?-->/s', '', $this->content);
        $this->content = $this->safeRegexReplace('/<script\b[^>]*>.*?<\/script>/is', '', $this->content);
        $this->content = $this->safeRegexReplace('/<style\b[^>]*>.*?<\/style>/is', '', $this->content);

        // Rozszerzona lista tagów do usunięcia - dodane formularze i elementy interaktywne
        $tags = [
            'img', 'svg', 'path', 'picture', 'source', 'video', 'audio', 'iframe',
            'canvas', 'noscript', 'object', 'embed', 'form', 'input', 'select',
            'button', 'textarea', 'option', 'fieldset', 'datalist'
        ];

        foreach ($tags as $tag) {
            if (microtime(true) - $startTime > $this->maxProcessingTime) break;
            $this->content = $this->safeRegexReplace("/<{$tag}[^>]*>.*?<\/{$tag}>/is", '', $this->content);
            $this->content = $this->safeRegexReplace("/<{$tag}[^>]*\/?>/is", '', $this->content);
        }

        // Usuwanie wszystkich atrybutów z wyjątkiem id i class (wersja z callbackiem)
        $this->content = preg_replace_callback(
            '/<([a-z][a-z0-9]*)\b([^>]*)>/is',
            function ($matches) {
                $tag = $matches[1];
                $attrs = $matches[2];
                $id = '';
                $class = '';
                if (preg_match('/\s(id)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>"]+)/i', $attrs, $idMatch)) {
                    $id = ' id=' . $idMatch[2];
                }
                if (preg_match('/\s(class)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>"]+)/i', $attrs, $classMatch)) {
                    $class = ' class=' . $classMatch[2];
                }
                return "<{$tag}{$id}{$class}>";
            },
            $this->content
        );

        for ($i = 0; $i < 2; $i++) {
            if (microtime(true) - $startTime > $this->maxProcessingTime) break;
            $prevContent = $this->content;
            $this->content = $this->safeRegexReplace('/<([a-z][a-z0-9]*(?:\:[a-z][a-z0-9]*)?)>\s*<\/\\1>/is', '', $this->content);
            if ($prevContent === $this->content) break;
        }

        $this->content = $this->safeRegexReplace('/\s{2,}/s', ' ', $this->content);
        $this->content = trim($this->content);

        return $this->content;
    }

    /**
     * Przetwarza pojedynczy fragment HTML
     */
    private function processHtmlChunk(string $chunk): string
    {
        $chunk = $this->safeRegexReplace('/<!--.*?-->/s', '', $chunk);

        // Rozszerzona lista tagów do usunięcia dla fragmentów
        $tags = [
            'img', 'svg', 'path', 'picture', 'source', 'iframe',
            'form', 'input', 'select', 'button', 'textarea',
            'option', 'fieldset'
        ];

        foreach ($tags as $tag) {
            $chunk = $this->safeRegexReplace("/<{$tag}[^>]*>.*?<\/{$tag}>/is", '', $chunk);
            $chunk = $this->safeRegexReplace("/<{$tag}[^>]*\/?>/is", '', $chunk);
        }

        // Usuwanie wszystkich atrybutów z wyjątkiem id i class (wersja z callbackiem)
        $chunk = preg_replace_callback(
            '/<([a-z][a-z0-9]*)\b([^>]*)>/is',
            function ($matches) {
                $tag = $matches[1];
                $attrs = $matches[2];
                $id = '';
                $class = '';
                if (preg_match('/\s(id)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>"]+)/i', $attrs, $idMatch)) {
                    $id = ' id=' . $idMatch[2];
                }
                if (preg_match('/\s(class)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>"]+)/i', $attrs, $classMatch)) {
                    $class = ' class=' . $classMatch[2];
                }
                return "<{$tag}{$id}{$class}>";
            },
            $chunk
        );

        return $chunk;
    }

    /**
     * Przetwarza duże dokumenty HTML fragmentami
     */
    private function processLargeHtml(float $startTime): string
    {
        // Najpierw usuńmy niepotrzebne duże bloki
        $this->content = $this->safeRegexReplace('/<script\b[^>]*>.*?<\/script>/is', '', $this->content);
        $this->content = $this->safeRegexReplace('/<style\b[^>]*>.*?<\/style>/is', '', $this->content);

        // Rozdziel dokument na części, które można bezpiecznie przetworzyć
        $chunks = $this->splitHtmlIntoChunks($this->content);
        $processedChunks = [];

        foreach ($chunks as $chunk) {
            if (microtime(true) - $startTime > $this->maxProcessingTime) {
                // Jeśli przekroczono limit czasu, zachowaj chunk bez przetwarzania
                $processedChunks[] = $chunk;
                continue;
            }

            // Przetwórz chunk
            $processed = $this->processHtmlChunk($chunk);
            $processedChunks[] = $processed;
        }

        // Połącz fragmenty i wykonaj końcowe czyszczenie
        $result = implode('', $processedChunks);
        $result = $this->safeRegexReplace('/\s{2,}/s', ' ', $result);

        return trim($result);
    }

    /**
     * Dzieli duży dokument HTML na mniejsze fragmenty do przetworzenia
     */
    private function splitHtmlIntoChunks(string $html): array
    {
        $chunks = [];
        $len = strlen($html);

        // Jeśli dokument jest mniejszy niż maksymalny rozmiar fragmentu, zwróć go w całości
        if ($len <= $this->maxChunkSize) {
            return [$html];
        }

        // Znajdź punkty podziału przy znacznikach końca elementów
        $startPos = 0;
        while ($startPos < $len) {
            $endPos = min($startPos + $this->maxChunkSize, $len);

            // Jeśli nie doszliśmy do końca, znajdź bezpieczny punkt podziału
            if ($endPos < $len) {
                // Znajdź najbliższy znacznik zamykający tag
                $tmpPos = strrpos(substr($html, $startPos, $endPos - $startPos), '</');
                if ($tmpPos !== false) {
                    // Znajdź koniec tagu zamykającego
                    $tagEndPos = strpos($html, '>', $startPos + $tmpPos);
                    if ($tagEndPos !== false && $tagEndPos < $endPos + 100) {
                        $endPos = $tagEndPos + 1;
                    }
                }
            }

            $chunks[] = substr($html, $startPos, $endPos - $startPos);
            $startPos = $endPos;
        }

        return $chunks;
    }

    /**
     * Bezpieczne wykonanie preg_replace z obsługą błędów
     */
    private function safeRegexReplace(string $pattern, string $replacement, string $subject): string
    {
        // Sprawdź, czy string jest pusty
        if ($subject === '') {
            return '';
        }

        $result = @preg_replace($pattern, $replacement, $subject);

        // Sprawdź, czy wystąpił błąd
        if ($result === null) {
            // Zapisz błąd w logach
            error_log('PCRE error in HtmlCleaner: ' . preg_last_error_msg());

            // Zastosuj alternatywne podejście lub zwróć oryginalny string
            return $subject;
        }

        return $result;
    }

    public function extractHeadings(): array
    {
        if (!empty($this->extractedHeadings)) {
            return $this->extractedHeadings;
        }

        return $this->extractHeadingsWithRegex();
    }

    /**
     * Ekstrahuje nagłówki używając wyrażeń regularnych
     */
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
}