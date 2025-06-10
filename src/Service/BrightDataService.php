<?php
declare(strict_types=1);

namespace CrawlToolkit\Service;

use CrawlToolkit\Enum\FetchType;
use CrawlToolkit\Enum\Language;
use Exception;
use RuntimeException;

/**
 * Service class for interacting with BrightData API for web crawling and SERP data retrieval.
 *
 * This service provides functionality to fetch web content and search results using
 * BrightData's API, supporting both HTML and Markdown formats.
 */
class BrightDataService
{
    private const float REQUEST_DELAY = 2.0;
    private float $lastRequestTime = 0;
    private const string API_URL = 'https://api.brightdata.com/request';
    private const array SKIP_WEBSITES = [
        '.ru',
        '.by',
    ];

    /**
     * Initializes the BrightDataService with required API keys and zones.
     *
     * @param string $brightDataSerpKey API key for BrightData SERP service
     * @param string $brightDataSerpZone Zone for BrightData SERP service
     * @param string $brightDataCrawlKey API key for BrightData Crawler service
     * @param string $brightDataCrawlZone Zone for BrightData Crawler service
     */
    public function __construct(
        private string $brightDataSerpKey,
        private string $brightDataSerpZone,
        private string $brightDataCrawlKey,
        private string $brightDataCrawlZone,
    )
    {
    }

    /**
     * Fetches content from a specified URL using BrightData's crawler service.
     *
     * @param string $url The URL to fetch content from
     * @param FetchType $fetchType The desired output format ('markdown' or 'html')
     * @return string|null The fetched content or null if the request fails
     * @throws RuntimeException When an error occurs during the request
     */
    public function fetchUrl(string $url, FetchType $fetchType = FetchType::Html): ?string
    {
        // Skip fetching content from URLs that match skip patterns
        if (array_any(self::SKIP_WEBSITES, fn($skip) => str_contains($url, $skip))) {
            throw new RuntimeException('This site is not supported: ' . $url);
        }

        $this->respectRateLimit();

        $headers = [
            'Authorization: Bearer ' . $this->brightDataCrawlKey,
            'Content-Type: application/json',
        ];

        $payload = [
            'zone' => $this->brightDataCrawlZone,
            'url' => $url,
            'format' => 'raw'
        ];

        if ($fetchType === FetchType::Markdown) {
            $payload['data_format'] = 'markdown';
        }

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 320,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        try {
            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                throw new RuntimeException('cURL Error: ' . curl_error($ch));
            }

            if ($statusCode === 200) {
                return $response;
            }
        } catch (Exception $e) {
            throw new RuntimeException('Error fetching Brightdata: ' . $e->getMessage());
        } finally {
            curl_close($ch);
        }

        return null;
    }

    /**
     * Retrieves top URLs from Google search results for a given keyword.
     *
     * @param string $keyword The search keyword
     * @param int $maxResults Maximum number of results to return (default: 20)
     * @param string $countryCode Country code for localized results (default: 'pl')
     * @param string|null $url Custom search URL (optional)
     * @param array $collectedUrls Previously collected URLs for pagination
     * @return array<string> Array of unique URLs from search results
     * @throws RuntimeException When an error occurs during the request or processing
     */
    public function getTopUrls(
        string $keyword, 
        int $maxResults = 20, 
        string $countryCode = 'pl', 
        ?string $url = null, 
        array $collectedUrls = []
    ): array {
        $countryCode = Language::fromCountryCode($countryCode)->mapForbiddenLangToDefault()->getCountryCode();

        $headers = [
            'Authorization: Bearer ' . $this->brightDataSerpKey,
            'Content-Type: application/json',
        ];

        $url = $url ?? 'https://www.google.com/search?q=' . urlencode($keyword);
        $url .= '&gl=' . $countryCode . '&brd_json=1';

        $payload = [
            'zone' => $this->brightDataSerpZone,
            'url' => $url,
            'format' => 'raw'
        ];

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 180,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        try {
            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                throw new RuntimeException('cURL Error: ' . curl_error($ch));
            }

            curl_close($ch);

            if ($statusCode === 200) {
                $responseData = json_decode($response, true);

                if (empty($responseData)) {
                    if (!empty($collectedUrls)) {
                        return array_slice($collectedUrls, 0, $maxResults);
                    }

                    throw new RuntimeException('Empty response from BrightData');
                }

                $nextPageUrl = $this->getNextPageUrl($responseData);
                if (empty($responseData['organic'])) {
                    if (empty($nextPageUrl) && !empty($collectedUrls)) {
                        // If no next page and we have collected any URLs, return them
                        return array_slice($collectedUrls, 0, $maxResults);
                    }

                    if (!empty($nextPageUrl)) {
                        // If next page URL is available, recursively fetch more URLs
                        return $this->getTopUrls($keyword, $maxResults, $countryCode, $nextPageUrl, $collectedUrls);
                    }

                    throw new RuntimeException('Unable to get top URLs');
                }

                $urls = $collectedUrls;
                foreach ($responseData['organic'] as $item) {
                    if (isset($item['link'])) {
                        $urls[] = $item['link'];
                    }
                }

                $urls = array_values(array_unique($urls));

                if (count($urls) < $maxResults && $nextPageUrl !== null) {
                    // Recursively fetch more URLs from the next page
                    return $this->getTopUrls($keyword, $maxResults, $countryCode, $nextPageUrl, $urls);
                }

                // Filter out URLs that match any of the skip patterns
                $urls = array_filter($urls, function ($url) {
                    foreach (self::SKIP_WEBSITES as $skip) {
                        if (str_contains($url, $skip)) {
                            return false;
                        }
                    }
                    return true;
                });

                return array_slice($urls, 0, $maxResults);
            } else {
                throw new RuntimeException('Request failed with status code ' . $statusCode);
            }
        } catch (Exception $e) {
            throw new RuntimeException('Error during get top URLs: ' . $e->getMessage());
        } finally {
            curl_close($ch);
        }
    }

    /**
     * Extracts the next page URL from the search results response.
     *
     * @param array $responseData The decoded response data from BrightData
     * @return string|null The URL for the next page of results, or null if not available
     */
    private function getNextPageUrl(array $responseData): ?string
    {
        if (!empty($responseData['pagination']['next_page_link'])) {
            return $responseData['pagination']['next_page_link'];
        }

        return null;
    }

    private function respectRateLimit(): void
    {
        $currentTime = microtime(true);
        $timeSinceLastRequest = $currentTime - $this->lastRequestTime;

        if ($timeSinceLastRequest < self::REQUEST_DELAY && $this->lastRequestTime > 0) {
            $sleepTime = (self::REQUEST_DELAY - $timeSinceLastRequest) * 1000000;
            usleep((int)$sleepTime);
        }
    }
}
