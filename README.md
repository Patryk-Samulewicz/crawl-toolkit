# Crawl Toolkit

A powerful PHP toolkit for web crawling and content analysis, integrating Bright Data and OpenRouter services.

## Features

- Google SERP data retrieval using Bright Data
- Web crawling capabilities with Bright Data
- Content analysis powered by OpenRouter AI
- Simple and intuitive API
- Multi-language support

## Requirements

- PHP 8.4 or higher
- Composer
- Bright Data account with SERP and Crawler access
- OpenRouter API key

## Installation

```bash
composer require patryk-samulewicz/crawl-toolkit
```

## Configuration

You'll need to provide the following credentials:

- Bright Data SERP API Key
- Bright Data SERP Zone
- Bright Data Crawler API Key
- Bright Data Crawler Zone
- OpenRouter API Key

## Usage

```php
use CrawlToolkit\CrawlToolkit;
use CrawlToolkit\Enum\Language;

$toolkit = new CrawlToolkit(
    'your-brightdata-serp-key',
    'your-brightdata-serp-zone',
    'your-brightdata-crawl-key',
    'your-brightdata-crawl-zone',
    'your-openrouter-key'
);

// Get top URLs for a keyword
$urls = $toolkit->getTopUrls('example keyword', 20, Language::POLISH);

// Analyze content
$texts = [
    ['url' => 'https://example.com', 'content' => 'Sample content...']
];
$analysis = $toolkit->analyzeText('example keyword', $texts, Language::ENGLISH);

// Get available languages
$availableLanguages = CrawlToolkit::getAvailableLanguages();
```

## Supported Languages

The toolkit supports the following languages:

- English (en)
- Polish (pl)
- German (de)
- French (fr)
- Spanish (es)
- Italian (it)

You can use the `Language` enum to specify the language for any operation:

```php
use CrawlToolkit\Enum\Language;

// Example with different languages
$toolkit->makeKeywordAnalysis('keyword', 20, Language::GERMAN);
$toolkit->processConnectionPhraseToContent('phrase', 'content', Language::FRENCH);
```

## Testing

Run the test suite:

```bash
composer test
```

## License

MIT License
