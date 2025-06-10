<?php
declare(strict_types=1);

namespace CrawlToolkit\Service;

use CrawlToolkit\Enum\FetchType;
use CrawlToolkit\Service\ContentCleaner\ContentCleanerFactory;
use Exception;
use RuntimeException;

/**
 * Serwis do pobierania zawartości stron internetowych.
 * 
 * Zapewnia podstawową funkcjonalność pobierania zawartości URL-i
 * z obsługą limitów zapytań i błędów.
 */
class UrlFetchService
{
    private const float REQUEST_DELAY = 1.0;
    private float $lastRequestTime = 0;
    private const int MAX_RETRIES = 2;
    private const int CURL_TIMEOUT = 30;
    private const array RETRY_STATUS_CODES = [408, 429, 500, 502, 503, 504];

    /**
     * Pobiera zawartość z podanego URL-a.
     *
     * @param string $url URL do pobrania
     * @return array{content: string, headers: array, statusCode: int}|null Dane strony lub null w przypadku błędu
     * @throws RuntimeException Gdy wystąpi błąd podczas pobierania
     */
    public function fetchUrl(string $url, FetchType $fetchType = FetchType::Html): ?array
    {
        $this->respectRateLimit();

        $defaultHeaders = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9',
            'Accept-Language: pl,en-US;q=0.7,en;q=0.3',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Cache-Control: max-age=0',
        ];

        $headers = array_merge($defaultHeaders);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => self::CURL_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::CURL_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_COOKIEFILE => '',
            CURLOPT_COOKIEJAR => '',
        ]);

        $retryCount = 0;
        $lastError = null;

        while ($retryCount < self::MAX_RETRIES) {
            try {
                $response = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

                if (curl_errno($ch)) {
                    throw new RuntimeException('Błąd cURL: ' . curl_error($ch));
                }

                if ($statusCode === 200) {
                    //$headers = $this->parseHeaders(substr($response, 0, $headerSize));
                    $content = substr($response, $headerSize);

                    $factory = new ContentCleanerFactory();
                    $cleaner = $factory->create($fetchType, $content);
                    $content = $cleaner->clean();

                    return [
                        'content' => $content,
                        'statusCode' => $statusCode
                    ];
                }

                if (in_array($statusCode, self::RETRY_STATUS_CODES, true)) {
                    $retryCount++;
                    if ($retryCount < self::MAX_RETRIES) {
                        $sleepTime = pow(2, $retryCount) * 1000000;
                        usleep((int)$sleepTime);
                        continue;
                    }
                }

                throw new RuntimeException('Nieprawidłowy kod odpowiedzi: ' . $statusCode);
            } catch (Exception $e) {
                $lastError = $e;
                $retryCount++;
                
                if ($retryCount < self::MAX_RETRIES) {
                    $sleepTime = pow(2, $retryCount) * 1000000;
                    usleep((int)$sleepTime);
                    continue;
                }
            } finally {
                curl_close($ch);
            }
        }

        throw new RuntimeException('Błąd podczas pobierania URL po ' . self::MAX_RETRIES . ' próbach: ' . ($lastError ? $lastError->getMessage() : 'Nieznany błąd'));
    }

    /**
     * Parsuje nagłówki HTTP z odpowiedzi.
     *
     * @param string $headerString String zawierający nagłówki
     * @return array<string, string> Tablica z nagłówkami
     */
    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        $headerLines = explode("\r\n", $headerString);

        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }

        return $headers;
    }

    /**
     * Zapewnia przestrzeganie limitu zapytań między kolejnymi requestami.
     */
    private function respectRateLimit(): void
    {
        $currentTime = microtime(true);
        $timeSinceLastRequest = $currentTime - $this->lastRequestTime;

        if ($timeSinceLastRequest < self::REQUEST_DELAY && $this->lastRequestTime > 0) {
            $sleepTime = (self::REQUEST_DELAY - $timeSinceLastRequest) * 1000000;
            usleep((int)$sleepTime);
        }

        $this->lastRequestTime = microtime(true);
    }
} 