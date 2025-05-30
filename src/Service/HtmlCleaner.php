<?php

namespace CrawlToolkit\Service;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Service class for cleaning and processing HTML content.
 *
 * This class provides functionality to clean HTML content by removing unnecessary elements,
 * attributes, and empty tags, as well as extracting headings from the content.
 */
class HtmlCleaner
{
    private Crawler $crawler;

    /**
     * Initializes the HtmlCleaner with HTML content.
     *
     * @param string $html The HTML content to clean
     */
    public function __construct(string $html)
    {
        if (empty($html)) {
            throw new \InvalidArgumentException('HTML content cannot be empty.');
        }

        $this->crawler = new Crawler();
        $this->crawler->addHtmlContent($html);
    }

    /**
     * Cleans the HTML content by removing unnecessary elements, attributes, and empty tags.
     *
     * @return string Cleaned HTML content
     */
    public function clean(): string
    {
        $this->removeElements('img');
        $this->removeElements('a');
        $this->removeElements('script');
        $this->removeElements('style');
        $this->removeElements('path');
        $this->removeElements('svg');
        $this->removeElements('symbol');
        $this->removeElements('link');
        $this->removeElements('picture');
        $this->removeElements('source');

        $this->removeAttributes();

        $this->removeEmptyTags();

        return $this->crawler->html();
    }

    /**
     * Extracts all headings (h1-h6) from the HTML content.
     *
     * @return array<array{tag: string, text: string}> Array of headings with their tags and text content
     */
    public function extractHeadings(): array
    {
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

    /**
     * Removes all elements matching the given CSS selector from the HTML content.
     *
     * @param string $selector CSS selector for elements to remove
     */
    private function removeElements(string $selector): void
    {
        $nodes = $this->crawler->filter($selector);
        if ($nodes->count() > 0) {
            $nodes->each(function (Crawler $node) {
                if ($node->getNode(0) && $node->getNode(0)->parentNode) {
                    $node->getNode(0)->parentNode->removeChild($node->getNode(0));
                }
            });
        }
    }

    /**
     * Removes all empty tags (tags with no text content) from the HTML content.
     */
    private function removeEmptyTags(): void
    {
        $nodes = $this->crawler->filter('*');
        if ($nodes->count() > 0) {
            $nodes->each(function (Crawler $node) {
                if (trim($node->text()) === '' && $node->getNode(0) && $node->getNode(0)->parentNode) {
                    $node->getNode(0)->parentNode->removeChild($node->getNode(0));
                }
            });
        }
    }

    /**
     * Removes all attributes from all elements in the HTML content.
     */
    private function removeAttributes(): void
    {
        $nodes = $this->crawler->filter('*');
        if ($nodes->count() > 0) {
            $nodes->each(function (Crawler $node) {
                foreach ($node as $domElement) {
                    if ($domElement && $domElement->attributes) {
                        while ($domElement->attributes->length) {
                            $domElement->removeAttribute($domElement->attributes->item(0)->name);
                        }
                    }
                }
            });
        }
    }
}
