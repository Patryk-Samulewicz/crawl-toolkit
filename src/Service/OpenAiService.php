<?php

declare(strict_types=1);

namespace CrawlToolkit\Service;

use Orhanerday\OpenAi\OpenAi;
use RuntimeException;
use Throwable;

/**
 * Service class for interacting with OpenAI API for AI-powered text analysis.
 *
 * This service provides functionality to analyze text content, extract keywords,
 * and process natural language using various AI models through the OpenAI API.
 */
class OpenAiService
{
    private string $GET_KEYWORDS_PROMPT;
    private string $EXTRACT_KEYWORD_CONNECTIONS_SYSTEM_PROMPT;
    private OpenAi $openAi;
    private array $variables;

    public function __construct(string $apiKey) {
        $this->openAi = new OpenAi($apiKey);
        $this->variables = [
            'current_date' => date('Y-m-d'),
        ];

        $this->GET_KEYWORDS_PROMPT = file_get_contents(__DIR__ . '/../Prompts/get_keywords_3.txt');
        $this->EXTRACT_KEYWORD_CONNECTIONS_SYSTEM_PROMPT = file_get_contents(__DIR__ . '/../Prompts/extract_keyword_connections_system.txt');
    }

    /**
     * Analyzes content for a given keyword across multiple texts
     *
     * @param string $keyword The keyword to analyze
     * @param array $texts Array of texts in format [['url' => string, 'content' => string]]
     * @param string $language Language for analysis (default: 'english')
     * @return array Analysis results from the AI model
     * @throws RuntimeException When the analysis fails
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

        $messages = [
            ["role" => "system", "content" => $this->GET_KEYWORDS_PROMPT],
            ["role" => "user", "content" => $body]
        ];

        $response = $this->callOpenAi(
            $messages,
            'gpt-4o',
            0.7,
            16000
        );

        if ($response === null) {
            return [];
        }

        return $this->responseJson($response);
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

        $messages = [
            ["role" => "system", "content" => $prompt],
            ["role" => "user", "content" => $body]
        ];

        $response = $this->callOpenAi(
            $messages,
            'gpt-4o',
            0.7,
            16000
        );

        if ($response === null) {
            return [];
        }

        return $this->responseJson($response);
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

    /**
     * Makes a request to the OpenAI API
     *
     * @param array<int, array{role: string, content: string}> $messages Messages for OpenAI API
     * @param string $model OpenAI model to use
     * @param float $temperature Controls randomness (0-1)
     * @param int $maxTokens Maximum tokens in response
     * @return string|null Response content or null on error
     */
    public function callOpenAi(
        array   $messages,
        string  $model = 'gpt-4',
        float   $temperature = 0.7,
        int     $maxTokens = 10000
    ): ?string {
        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ];

            $response = $this->openAi->chat($payload);

            if (!$response) {
                throw new RuntimeException('Empty response from OpenAI API');
            }

            $response = json_decode($response);

            if (!empty($response->error)) {
                throw new RuntimeException($response->error->message);
            }

            return $response->choices[0]->message->content ?? null;
        } catch (Throwable $e) {
            throw new RuntimeException('Error calling OpenAI API: ' . $e->getMessage());
        }
    }

    /**
     * @param string $response
     * @return array
     */
    private function responseJson(string $response): array
    {
        $cleanedResponse = trim($response);
        $cleanedResponse = preg_replace('/^```(json)?\s*|\s*```$/i', '', $cleanedResponse);

        $decodedResponse = json_decode($cleanedResponse, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResponse)) {
            return $decodedResponse;
        }

        return [];
    }
} 