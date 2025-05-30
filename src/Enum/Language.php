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

    public function getCountryCode(): string
    {
        return match($this) {
            self::ENGLISH => 'us',
            self::POLISH => 'pl',
            self::GERMAN => 'de',
            self::FRENCH => 'fr',
            self::SPANISH => 'es',
            self::ITALIAN => 'it',
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
            default => null,
        };
    }

    public static function getAvailableLanguages(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
} 