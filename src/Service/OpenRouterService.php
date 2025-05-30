<?php

declare(strict_types=1);

namespace CrawlToolkit\Service;

/**
 * Service class for interacting with OpenRouter API for AI-powered text analysis.
 *
 * This service provides functionality to analyze text content, extract keywords,
 * and process natural language using various AI models through the OpenRouter API.
 */
class OpenRouterService
{
    private const string API_BASE_URL = 'https://openrouter.ai/api/v1';
    private string $GET_KEYWORDS_PROMPT;
    private string $EXTRACT_KEYWORD_CONNECTIONS_SYSTEM_PROMPT;
    private array $variables = [];
    
    /**
     * Initializes the OpenRouterService with API key and loads prompt templates.
     *
     * @param string $apiKey OpenRouter API key
     */
    public function __construct(
        private readonly string $apiKey,
    ) {
        $this->GET_KEYWORDS_PROMPT = file_get_contents(__DIR__ . '/../Prompts/get_keywords_3.txt');

        $this->EXTRACT_KEYWORD_CONNECTIONS_SYSTEM_PROMPT = file_get_contents(__DIR__ . '/../Prompts/extract_keyword_connections_system.txt');

        $this->variables = [
            'current_date' => date('Y-m-d'),
        ];
    }

    /**
     * Makes a request to the OpenRouter API
     *
     * @param string $endpoint API endpoint to call
     * @param array $data Request payload data
     * @return array Decoded API response
     * @throws \RuntimeException When API communication fails or returns an error
     */
    public function request(string $endpoint, array $data = []): array
    {
        $ch = curl_init(self::API_BASE_URL . $endpoint);
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($data),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("Error communicating with OpenRouter API: $error");
        }
        
        curl_close($ch);
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['error']['message'] ?? 'Unknown error';
            throw new \RuntimeException("OpenRouter API error: $errorMessage");
        }
        
        return $decodedResponse;
    }

    /**
     * Analyzes content for a given keyword across multiple texts
     *
     * @param string $keyword The keyword to analyze
     * @param array $texts Array of texts in format [['url' => string, 'content' => string]]
     * @return array Analysis results from the AI model
     * @throws \RuntimeException When the analysis fails
     */
    public function analyzeKeyword(string $keyword, array $texts, string $language = 'english'): array
    {
        $this->variables['keyword'] = $keyword;
        $this->variables['language'] = $language;

        $maxFormattedTextTokens = 50000;

        $formattedTexts = '';
        foreach ($texts as $index => $text) {
            $formattedTexts .= "-----TEXT" . ($index + 1) . "-----\n";
            $formattedTexts .= "URL: " . $text['url'] . "\n";
            $formattedTexts .= "Content: " . json_encode($text['content']) . "\n";

            if ($this->calcTextTokens($formattedTexts) >= $maxFormattedTextTokens) {
                break;
            }
        }
        $formattedTexts .= "-----TEXT END-----\n";

        $body = '
- **Central Keyword**:
' . $keyword . '
- **Language**:
' . $language . '
- **Web Content Context**:
' . $formattedTexts;

        //$prompt = $this->replaceVariables($this->GET_KEYWORDS_PROMPT);

        $response = $this->request('/chat/completions', [
            'model' => 'openai/gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $this->GET_KEYWORDS_PROMPT],
                ['role' => 'user', 'content' => $body]
            ],
            'max_tokens' => 80000,
        ]);

        return $this->jsonResponse($response);
    }

    /**
     * Replaces variables in the prompt text with their corresponding values
     *
     * @param string $prompt The prompt template containing variables in [[variable]] format
     * @return string The prompt with variables replaced with their values
     */
    private function replaceVariables(string $prompt): string
    {
        return preg_replace_callback(
            '/\[\[(.*?)\]\]/',
            function ($matches) {
                $variableName = trim($matches[1]);
                return $this->variables[$variableName] ?? $matches[0];
            },
            $prompt
        );
    }

    /**
     * Extracts content related to a specific phrase from the given text
     *
     * @param string $keyword The phrase to search for
     * @param string $content The text content to analyze
     * @param string $lang Language of the content (default: 'english')
     * @return array Extracted content and analysis results
     */
    public function extractPhraseContent(string $keyword, string $content, string $lang = 'english'): array
    {
        $this->variables['lang'] = $lang;
        $this->variables['phrase'] = $keyword;

        $prompt = $this->replaceVariables($this->EXTRACT_KEYWORD_CONNECTIONS_SYSTEM_PROMPT);
        $body = "###Text to check \n" . $content . "\n\n";

        $response = $this->request('/chat/completions', [
            'model' => 'openai/gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => $body]
            ],
            'max_tokens' => 20000,
            'temperature' => 0.7,
        ]);

        return $this->jsonResponse($response);
    }

    /**
     * Calculates an approximate number of tokens in the given text
     *
     * @param string $text The text to calculate tokens for
     * @return int Approximate number of tokens
     */
    public function calcTextTokens(string $text): int
    {
        $len = strlen($text);

        if ($len === 0) {
            return 0;
        }

        if ($len < 4) {
            return 1;
        }

        return (int) ceil($len / 4);
    }

    private function jsonResponse(array $response): array
    {
        if (!isset($response['choices'][0]['message']['content'])) {
            return [];
        }

        $content = $response['choices'][0]['message']['content'];
        $cleanedResponse = trim($content);
        $cleanedResponse = preg_replace('/^```(json)?\s*|\s*```$/i', '', $cleanedResponse);

        $decodedResponse = json_decode($cleanedResponse, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResponse)) {
            return $decodedResponse;
        }

        return [];
    }
} 