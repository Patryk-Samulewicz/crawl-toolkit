<?php

namespace CrawlToolkit;

use CrawlToolkit\Service\HtmlCleaner;
use Exception;
use CrawlToolkit\Service\BrightDataService;
use CrawlToolkit\Service\OpenRouterService;
use RuntimeException;
use CrawlToolkit\Service\MarkdownCleaner;
use CrawlToolkit\Enum\Language;

/**
 * Main class for the CrawlToolkit package that provides functionality for web crawling,
 * content analysis, and text processing.
 *
 * This class integrates with BrightData for web crawling and OpenRouter for AI-powered
 * content analysis. It provides methods for fetching URLs, cleaning content,
 * and analyzing text using various services.
 */
final readonly class CrawlToolkit
{
    private BrightDataService $brightDataService;
    private OpenRouterService $openRouterService;

    /**
     * Initializes the CrawlToolkit with required API keys and zones.
     *
     * @param string $brightDataSerpKey API key for BrightData SERP service
     * @param string $brightDataSerpZone Zone for BrightData SERP service
     * @param string $brightDataCrawlKey API key for BrightData Crawler service
     * @param string $brightDataCrawlZone Zone for BrightData Crawler service
     * @param string $openRouterKey API key for OpenRouter service
     */
    public function __construct(
        private string $brightDataSerpKey,
        private string $brightDataSerpZone,
        private string $brightDataCrawlKey,
        private string $brightDataCrawlZone,
        private string $openRouterKey
    ) {
        $this->brightDataService = new BrightDataService(
            $this->brightDataSerpKey,
            $this->brightDataSerpZone,
            $this->brightDataCrawlKey,
            $this->brightDataCrawlZone,
        );

        $this->openRouterService = new OpenRouterService($this->openRouterKey);
    }

    /**
     * Retrieves top URLs from Google search for the given keyword
     *
     * @param string $keyword Keyword to search for
     * @param int $maxResults Maximum number of results (default: 20)
     * @param Language $language Language for search results (default: Language::ENGLISH)
     * @return array<string> List of URLs
     * @throws RuntimeException When an error occurs while fetching results
     */
    public function getTopUrls(string $keyword, int $maxResults = 20, Language $language = Language::ENGLISH): array
    {
        try {
            return $this->brightDataService->getTopUrls($keyword, $maxResults, $language->getCountryCode());
        } catch (Exception $e) {
            throw new RuntimeException('Error while fetching top URLs: ' . $e->getMessage());
        }
    }

    /**
     * Analyzes text using OpenRouter API
     *
     * @param string $keyword The keyword to analyze against
     * @param array $texts Array of texts in format [['url' => string, 'content' => string]]
     * @param Language $language Language for analysis (default: Language::ENGLISH)
     * @return array Analysis result containing insights about the provided texts
     * @throws RuntimeException When an error occurs during text analysis
     */
    public function analyzeText(string $keyword, array $texts, Language $language = Language::ENGLISH): array
    {
        try {
            return $this->openRouterService->analyzeKeyword($keyword, $texts, $language->value);
        } catch (Exception $e) {
            throw new RuntimeException('Error during text analysis: ' . $e->getMessage());
        }
    }

    /**
     * Cleans Markdown content by removing images and links
     *
     * @param string $markdown Markdown content to clean
     * @return string Cleaned Markdown content
     * @throws RuntimeException When an error occurs during cleaning
     */
    public function cleanMarkdown(string $markdown): string
    {
        try {
            return new MarkdownCleaner($markdown)->clean();
        } catch (Exception $e) {
            throw new RuntimeException('Error while cleaning Markdown content: ' . $e->getMessage());
        }
    }

    /**
     * Cleans HTML content by removing unnecessary elements and formatting
     *
     * @param string $html HTML content to clean
     * @return string Cleaned HTML content
     * @throws RuntimeException When an error occurs during cleaning
     */
    public function cleanHtml(string $html): string
    {
        try {
            $cleaner = new HtmlCleaner($html);
            return $cleaner->clean();
        } catch (Exception $e) {
            throw new RuntimeException('Error while cleaning HTML content: ' . $e->getMessage());
        }
    }

    /**
     * Fetches and cleans content for multiple URLs
     *
     * @param array<string> $urls Array of URLs to fetch and clean
     * @return array<array{url: string, content: string|null}> Array of URLs and their cleaned content
     * @throws RuntimeException When an error occurs during fetching or cleaning
     */
    public function fetchAndCleanUrls(array $urls): array
    {
        if (empty($urls)) {
            throw new RuntimeException('URLs array cannot be empty');
        }

        $result = [];

        foreach ($urls as $url) {
            try {
                $content = $this->brightDataService->fetchUrl($url);

                if ($content !== null) {
                    $cleanedContent = $this->cleanHtml($content);
                    $result[] = [
                        'url' => $url,
                        'content' => $cleanedContent
                    ];
                } else {
                    $result[] = [
                        'url' => $url,
                        'content' => null
                    ];
                }
            } catch (Exception $e) {
                throw new RuntimeException('Error while fetching or cleaning URL ' . $url . ': ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Processes a connection phrase to extract content using OpenRouter API
     *
     * @param string $phrase Connection phrase to process
     * @param string $content Content to analyze
     * @param Language $language Language of the content (default: Language::ENGLISH)
     * @return array Processed content with extracted information
     * @throws RuntimeException When an error occurs during processing
     */
    public function processConnectionPhraseToContent(string $phrase, string $content, Language $language = Language::ENGLISH): array
    {
        try {
            return $this->openRouterService->extractPhraseContent($phrase, $content, $language->value);
        } catch (Exception $e) {
            throw new RuntimeException('Error processing connection phrase: ' . $e->getMessage());
        }
    }

    /**
     * Extracts headers from multiple URLs
     *
     * @param array<string> $urls Array of URLs to process
     * @return array<array{url: string, headings: array}> Array containing URLs and their extracted headings
     * @throws RuntimeException When an error occurs during fetching or processing
     */
    public function getHeadersFromUrls(array $urls): array
    {
        if (empty($urls)) {
            throw new RuntimeException('URLs array cannot be empty');
        }

        $result = [];
        foreach ($urls as $url) {
            try {
                $content = $this->brightDataService->fetchUrl($url);
                if (empty($content)) {
                    continue;
                }

                $cleaner = new HtmlCleaner($content);
                $headings = $cleaner->extractHeadings();

                $result[] = [
                    'url' => $url,
                    'headings' => $headings
                ];
            } catch (Exception $e) {
                throw new RuntimeException('Error fetching headers for URL ' . $url . ': ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Performs comprehensive keyword analysis across multiple URLs
     *
     * @param string $keyword Keyword to analyze
     * @param int $maxUrls Maximum number of URLs to process (default: 20)
     * @param Language $language Language for analysis (default: Language::ENGLISH)
     * @return array Analysis results containing insights about the keyword
     * @throws RuntimeException When an error occurs during analysis
     */
    public function makeKeywordAnalysis(string $keyword, int $maxUrls = 20, Language $language = Language::ENGLISH): array
    {
        try {
            $result = [];

            $urls = $this->brightDataService->getTopUrls($keyword, $maxUrls, $language->getCountryCode());

            do {
                $url = array_shift($urls);
                if ($url === null) {
                    break; // No more URLs to process
                }

                $content = $this->brightDataService->fetchUrl($url, 'markdown');
                if (empty($content)) {
                    continue;
                }

                $cleanedContent = new MarkdownCleaner($content)->clean();

                $extractedContent = $this->openRouterService->extractPhraseContent($keyword, $cleanedContent, $language->value);
                if (!empty($extractedContent)) {
                    $result[] = [
                        'url' => $url,
                        'content' => $extractedContent
                    ];
                }

            } while (!empty($urls) && count($result) < $maxUrls);

            return $this->openRouterService->analyzeKeyword($keyword, $result, $language->value);
        } catch (Exception $e) {
            throw new RuntimeException('Error during keyword analysis: ' . $e->getMessage());
        }
    }

    /**
     * Fetches headers for a given keyword by retrieving top URLs and extracting their headings.
     *
     * @param string $keyword
     * @param int $maxUrls
     * @param Language $language
     * @return array[]
     */
    public function getHeadersForKeyword(string $keyword, int $maxUrls = 20, Language $language = Language::ENGLISH): array
    {
        try {
            $urls = $this->brightDataService->getTopUrls($keyword, $maxUrls, $language->getCountryCode());
            return $this->getHeadersFromUrls($urls);
        } catch (Exception $e) {
            throw new RuntimeException('Error fetching headers for keyword: ' . $e->getMessage());
        }
    }

    /**
     * Returns list of available languages
     *
     * @return array<string> List of available languages
     */
    public static function getAvailableLanguages(): array
    {
        return Language::getAvailableLanguages();
    }
} 
