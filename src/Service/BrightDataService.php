<?php
declare(strict_types=1);

namespace CrawlToolkit\Service;

use Exception;
use RuntimeException;

/**
 * Service class for interacting with BrightData API for web crawling and SERP data retrieval.
 *
 * This service provides functionality to fetch web content and search results using
 * BrightData's API, supporting both HTML and Markdown formats.
 */
readonly class BrightDataService
{
    private const string API_URL = 'https://api.brightdata.com/request';

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
     * @param string $dataFormat The desired output format ('markdown' or 'html')
     * @return string|null The fetched content or null if the request fails
     * @throws RuntimeException When an error occurs during the request
     */
    public function fetchUrl(string $url, string $dataFormat = 'html'): ?string
    {
        $headers = [
            'Authorization: Bearer ' . $this->brightDataCrawlKey,
            'Content-Type: application/json',
        ];

        $payload = [
            'zone' => $this->brightDataCrawlZone,
            'url' => $url,
            'format' => 'raw'
        ];

        if ($dataFormat === 'markdown') {
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

            curl_close($ch);

            if ($statusCode === 200) {
                return $response;
            }
        } catch (Exception $e) {
            throw new RuntimeException('Error fetching Brightdata: ' . $e->getMessage());
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

                if (empty($responseData['organic'])) {
                    throw new RuntimeException('Unable to get top URLs');
                }

                $urls = $collectedUrls;
                foreach ($responseData['organic'] as $item) {
                    if (isset($item['link'])) {
                        $urls[] = $item['link'];
                    }
                }

                $urls = array_values(array_unique($urls));

                // Check if we need more URLs and if there's a next page
                $nextPageUrl = $this->getNextPageUrl($responseData);

                if (count($urls) < $maxResults && $nextPageUrl !== null) {
                    // Recursively fetch more URLs from the next page
                    return $this->getTopUrls($keyword, $maxResults, $countryCode, $nextPageUrl, $urls);
                }

                return array_slice($urls, 0, $maxResults);
            } else {
                throw new RuntimeException('Request failed with status code ' . $statusCode);
            }
        } catch (Exception $e) {
            throw new RuntimeException('Error during get top URLs: ' . $e->getMessage());
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
}
