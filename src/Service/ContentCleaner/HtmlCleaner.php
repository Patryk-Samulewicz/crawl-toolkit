<?php

namespace CrawlToolkit\Service\ContentCleaner;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Service class for cleaning and processing HTML content.
 */
class HtmlCleaner extends AbstractContentCleaner
{
    private Crawler $crawler;
    private int $maxProcessingTime = 30; // maksymalny czas przetwarzania w sekundach
    private bool $initialized = false;

    public function __construct(string $html)
    {
        parent::__construct($html);
        $this->crawler = new Crawler();
    }

    protected function validateContent(): bool
    {
        return !empty($this->content);
    }

    private function initCrawler(): void
    {
        if (!$this->initialized) {
            $this->crawler->addHtmlContent($this->content);
            $this->initialized = true;
        }
    }

    /**
     * Cleans the HTML content by removing unnecessary elements, attributes, and empty tags.
     *
     * @return string Cleaned HTML content
     */
    public function clean(): string
    {
        $startTime = microtime(true);
        $this->initCrawler();

        // Usuń wszystkie niepotrzebne elementy jednym selektorem
        $this->removeElements();

        // Usuń atrybuty, ale przerwij jeśli przekroczy limit czasu
        $this->removeAttributes($startTime);

        // Usuń puste tagi z limitem czasu
        $this->removeEmptyTags($startTime);

        return $this->crawler->html();
    }

    /**
     * Extracts all headings (h1-h6) from the HTML content.
     *
     * @return array<array{tag: string, text: string}> Array of headings
     */
    public function extractHeadings(): array
    {
        $this->initCrawler();

        $headings = [];
        $nodes = $this->crawler->filter('h1, h2, h3, h4, h5, h6');
        if ($nodes->count() > 0) {
            $nodes->each(function (Crawler $node) use (&$headings) {
                $headings[] = [
                    'tag' => $node->nodeName(),
                    'text' => trim($node->text()),
                ];
            });
        }
        return $headings;
    }

    private function removeElements(): void
    {
        $nodes = $this->crawler->filter('img, a, script, style, path, svg, symbol, link, picture, source');
        if ($nodes->count() > 0) {
            // Zbierz węzły do usunięcia w tablicy
            $nodesToRemove = [];
            $nodes->each(function (Crawler $node) use (&$nodesToRemove) {
                if ($node->getNode(0) && $node->getNode(0)->parentNode) {
                    $nodesToRemove[] = $node->getNode(0);
                }
            });

            // Usuń węzły w odwrotnej kolejności
            foreach (array_reverse($nodesToRemove) as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
    }

    private function removeAttributes(float $startTime): void
    {
        $nodes = $this->crawler->filter('*');
        if ($nodes->count() > 0) {
            $nodes->each(function (Crawler $node) use ($startTime) {
                // Przerwij jeśli przekroczono limit czasu
                if (microtime(true) - $startTime > $this->maxProcessingTime) {
                    return false;
                }

                $element = $node->getNode(0);
                if ($element && $element->hasAttributes()) {
                    while ($element->attributes->length) {
                        $element->removeAttribute($element->attributes->item(0)->name);
                    }
                }
            });
        }
    }

    private function removeEmptyTags(float $startTime): void
    {
        // Przetwarzanie wielokrotne, od wewnątrz na zewnątrz
        for ($i = 0; $i < 3; $i++) { // Ograniczenie liczby iteracji
            if (microtime(true) - $startTime > $this->maxProcessingTime) {
                break;
            }

            $nodes = $this->crawler->filter('*');
            $nodesToRemove = [];

            $nodes->each(function (Crawler $node) use (&$nodesToRemove, $startTime) {
                if (microtime(true) - $startTime > $this->maxProcessingTime) {
                    return false;
                }

                if (trim($node->text()) === '' && !$node->children()->count() &&
                    $node->getNode(0) && $node->getNode(0)->parentNode) {
                    $nodesToRemove[] = $node->getNode(0);
                }
            });

            // Jeśli nie ma więcej pustych tagów, przerwij pętlę
            if (empty($nodesToRemove)) {
                break;
            }

            // Usuń zebrane puste węzły
            foreach ($nodesToRemove as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
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