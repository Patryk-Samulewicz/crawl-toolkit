<?php

namespace CrawlToolkit;

use CrawlToolkit\Enum\FetchType;
use CrawlToolkit\Enum\Language;
use CrawlToolkit\Service\BrightDataService;
use CrawlToolkit\Service\ContentCleaner\ContentCleanerFactory;
use CrawlToolkit\Service\ContentCleaner\HtmlCleaner;
use CrawlToolkit\Service\ContentCleaner\MarkdownCleaner;
use CrawlToolkit\Service\OpenAiService;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use RuntimeException;

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
    private OpenAiService $openAiService;

    /**
     * Initializes the CrawlToolkit with required API keys and zones.
     *
     * @param string $brightDataSerpKey API key for BrightData SERP service
     * @param string $brightDataSerpZone Zone for BrightData SERP service
     * @param string $brightDataCrawlKey API key for BrightData Crawler service
     * @param string $brightDataCrawlZone Zone for BrightData Crawler service
     * @param string $openAiKey API key for OpenAI service
     */
    public function __construct(
        private string $brightDataSerpKey,
        private string $brightDataSerpZone,
        private string $brightDataCrawlKey,
        private string $brightDataCrawlZone,
        private string $openAiKey
    ) {
        if (empty($brightDataSerpKey) || empty($brightDataSerpZone) ||
            empty($brightDataCrawlKey) || empty($brightDataCrawlZone) || empty($openAiKey)) {
            throw new RuntimeException('All API keys and zones must be provided.');
        }

        $this->brightDataService = new BrightDataService(
            $this->brightDataSerpKey,
            $this->brightDataSerpZone,
            $this->brightDataCrawlKey,
            $this->brightDataCrawlZone,
        );

        $this->openAiService = new OpenAiService($this->openAiKey);
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
            return $this->openAiService->analyzeKeyword($keyword, $texts, $language->value);
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
    public function fetchAndCleanUrls(array $urls, FetchType $fetchType = FetchType::Html): array
    {
        if (empty($urls)) {
            throw new RuntimeException('URLs array cannot be empty');
        }

        $result = [];
        foreach ($urls as $url) {
            try {
                $content = $this->brightDataService->fetchUrl($url, $fetchType);

                if (empty($content)) {
                    throw new RuntimeException('Error while fetching URL: ' . $url);
                }
            } catch (Exception) {
                $result[] = [
                    'url' => $url,
                    'content' => null
                ];

                continue;
            }

            $cleanerFactory = new ContentCleanerFactory();
            $cleaner = $cleanerFactory->create($fetchType, $content);

            $result[] = [
                'url' => $url,
                'content' => $cleaner->clean()
            ];
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
        $phrase = trim($phrase);
        $phrase = str_replace(["\r", "\n", "\t"], ' ', $phrase);
        $phrase = mb_convert_encoding($phrase, 'UTF-8', 'auto');

        try {
            return $this->openAiService->extractPhraseContent($phrase, $content, $language->value);
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
    public function getHeadersFromUrls(array $urls, FetchType $fetchType = FetchType::Html): array
    {
        if (empty($urls)) {
            throw new RuntimeException('URLs array cannot be empty');
        }

        $contents = $this->fetchAndCleanUrls($urls, $fetchType);

        if (empty($contents)) {
            throw new RuntimeException('No content fetched from provided URLs');
        }

        // Extract headings from the cleaned content
        $result = [];
        $cleanerFactory = new ContentCleanerFactory();
        foreach ($contents as $content) {
            if (empty($content['content'])) {
                continue; // Skip if content is empty
            }

            $cleaner = $cleanerFactory->create($fetchType, $content['content']);
            $headings = $cleaner->extractHeadings();

            $result[] = [
                'url' => $content['url'],
                'headings' => $headings
            ];
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
        $keyword = trim($keyword);
        $keyword = str_replace(["\r", "\n", "\t"], ' ', $keyword);
        $keyword = mb_convert_encoding($keyword, 'UTF-8', 'auto');

        try {
            $result = [];

            $urls = $this->brightDataService->getTopUrls($keyword, $maxUrls, $language->getCountryCode());

            do {
                $url = array_shift($urls);
                if ($url === null) {
                    break; // No more URLs to process
                }

                $content = $this->brightDataService->fetchUrl($url, FetchType::Markdown);
                if (empty($content)) {
                    continue;
                }

                $cleanedContent = new MarkdownCleaner($content)->clean();

                $extractedContent = $this->openAiService->extractPhraseContent($keyword, $cleanedContent, $language->value);
                if (!empty($extractedContent)) {
                    $result[] = [
                        'url' => $url,
                        'content' => $extractedContent
                    ];
                }

            } while (!empty($urls) && count($result) < $maxUrls);

            return $this->openAiService->analyzeKeyword($keyword, $result, $language->value);
        } catch (Exception $e) {
            throw new RuntimeException('Error during keyword analysis: ' . $e->getMessage());
        }
    }

    /**
     * Performs comprehensive keyword analysis across multiple URLs and extracts headers
     *
     * @param string $keyword Keyword to analyze
     * @param int $maxUrls Maximum number of URLs to process (default: 20)
     * @param Language $language Language for analysis (default: Language::ENGLISH)
     * @return array Analysis results containing insights about the keyword
     * @throws RuntimeException When an error occurs during analysis
     */
    public function makeKeywordAnalysisAndHeaders(string $keyword, int $maxUrls = 20, Language $language = Language::ENGLISH): array
    {
        $keyword = trim($keyword);
        $keyword = str_replace(["\r", "\n", "\t"], ' ', $keyword);
        $keyword = mb_convert_encoding($keyword, 'UTF-8', 'auto');

        try {
            $result = [];

            $urls = $this->brightDataService->getTopUrls($keyword, $maxUrls, $language->getCountryCode());

            do {
                $url = array_shift($urls);
                if ($url === null) {
                    break; // No more URLs to process
                }

                try {
                    $content = $this->brightDataService->fetchUrl($url, FetchType::Markdown);
                    if (empty($content)) {
                        continue;
                    }
                } catch (Exception) {
                    continue;
                }

                $content = mb_convert_encoding($content, 'UTF-8', 'auto');

                $cleaner = new MarkdownCleaner($content);
                $headings = $cleaner->extractHeadings();
                $cleanedContent = $cleaner->clean();

                $extractedContent = $this->openAiService->extractPhraseContent($keyword, $cleanedContent, $language->value);

                $result[] = [
                    'url' => $url,
                    'content' => $extractedContent,
                    'headings' => $headings
                ];

            } while (!empty($urls) && count($result) < $maxUrls);

            return [
                'analysis' => $this->openAiService->analyzeKeyword($keyword, $result, $language->value),
                'results' => $result
            ];

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
     * Fetches content from a specified URL using BrightData's crawler service.
     *
     * @param string $url The URL to fetch content from
     * @param FetchType $fetchType The desired output format ('markdown' or 'html')
     * @return string|null The fetched content or null if the request fails
     * @throws RuntimeException When an error occurs during the request
     */
    public function fetchUrlContent(string $url, FetchType $fetchType = FetchType::Html): ?string
    {
        try {
            return $this->brightDataService->fetchUrl($url, $fetchType);
        } catch (Exception $e) {
            throw new RuntimeException('Error fetching URL content: ' . $e->getMessage());
        }
    }

    /**
     * Makes keywords from content by extracting relevant phrases and analyzing them.
     *
     * @param string $keyword The keyword to analyze
     * @param string $url The URL associated with the content
     * @param string $content The content to analyze
     * @param Language $language Language for analysis (default: Language::ENGLISH)
     * @return array Analysis results containing insights about the keyword
     * @throws RuntimeException When an error occurs during analysis
     */
    public function makeKeywordsFromContent(string $keyword, string $url, string $content, Language $language = Language::ENGLISH): array
    {
        try {
            $extractedContent = $this->openAiService->extractPhraseContent($keyword, $content);

            if (empty($extractedContent)) {
                throw new RuntimeException('No content extracted for keyword: ' . $keyword);
            }


            return $this->openAiService->analyzeKeyword($keyword, [['url' => $url, 'content' => $extractedContent]], $language->value);

        } catch (Exception $e) {
            throw new RuntimeException('Error making keywords from content: ' . $e->getMessage());
        }
    }

    /**
     * Makes keywords from multiple contents by extracting relevant phrases and analyzing them.
     *
     * @param string $keyword The keyword to analyze
     * @param array $contents Array of contents in format [['url' => string, 'content' => string]]
     * @param Language $language Language for analysis (default: Language::ENGLISH)
     * @return array Analysis results containing insights about the keyword
     * @throws RuntimeException When an error occurs during analysis
     */
    public function makeKeywordsFromContents(string $keyword, array $contents, Language $language = Language::ENGLISH): array
    {
        if (empty($contents)) {
            throw new RuntimeException('Contents array cannot be empty');
        }

        try {
            $extractedContents = [];
            foreach ($contents as $content) {
                if (empty($content['content'])) {
                    continue; // Skip empty content
                }

                $extractedContent = $this->openAiService->extractPhraseContent($keyword, $content['content'], $language->value);
                if (!empty($extractedContent)) {
                    $extractedContents[] = [
                        'url' => $content['url'],
                        'content' => $extractedContent
                    ];
                }
            }

            if (empty($extractedContents)) {
                throw new RuntimeException('No valid contents extracted for keyword: ' . $keyword);
            }

            return $this->openAiService->analyzeKeyword($keyword, $extractedContents, $language->value);
        } catch (Exception $e) {
            throw new RuntimeException('Error making keyword from contents: ' . $e->getMessage());
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
