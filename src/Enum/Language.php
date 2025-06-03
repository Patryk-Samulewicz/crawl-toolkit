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
            default => null,
        };
    }

    public static function getAvailableLanguages(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
} 