<?php

declare(strict_types=1);

namespace CrawlToolkit\Enum;

enum Language: string
{
    case ENGLISH = 'english';
    case POLISH = 'polish';
    case GERMAN = 'german';
    case FRENCH = 'french';
    case SPANISH = 'spanish';
    case ITALIAN = 'italian';
    case UKRAINIAN = 'ukrainian';
    case CZECH = 'czech';
    case PORTUGUESE = 'portuguese';
    case CROATIAN = 'croatian';
    case SLOVAK = 'slovak';
    case DUTCH = 'dutch';
    case RUSSIAN = 'russian';
    case HUNGARIAN = 'hungarian';


    public function getCountryCode(): string
    {
        return match($this) {
            self::ENGLISH => 'us',
            self::POLISH => 'pl',
            self::GERMAN => 'de',
            self::FRENCH => 'fr',
            self::SPANISH => 'es',
            self::ITALIAN => 'it',
            self::UKRAINIAN => 'ua',
            self::CZECH => 'cz',
            self::PORTUGUESE => 'pt',
            self::CROATIAN => 'hr',
            self::SLOVAK => 'sk',
            self::DUTCH => 'nl',
            self::RUSSIAN => 'ru',
            self::HUNGARIAN => 'hu',
        };
    }

    public static function fromCountryCode(string $code): ?self
    {
        return match(strtolower($code)) {
            'us' => self::ENGLISH,
            'pl' => self::POLISH,
            'de' => self::GERMAN,
            'fr' => self::FRENCH,
            'es' => self::SPANISH,
            'it' => self::ITALIAN,
            'ua' => self::UKRAINIAN,
            'cz' => self::CZECH,
            'pt' => self::PORTUGUESE,
            'hr' => self::CROATIAN,
            'sk' => self::SLOVAK,
            'nl' => self::DUTCH,
            'ru' => self::RUSSIAN,
            'hu' => self::HUNGARIAN,
            default => null,
        };
    }

    public static function getAvailableLanguages(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
} 