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
    case GREEK = 'greek';
    case AFRIKAANS = 'afrikaans';
    case ALBANIAN = 'albanian';
    case ARABIC = 'arabic';
    case ARMENIAN = 'armenian';
    case AZERBAIJANI = 'azerbaijani';
    case BELARUSIAN = 'belarusian';
    case BENGALI = 'bengali';
    case BULGARIAN = 'bulgarian';
    case BURMESE = 'burmese';
    case CATALAN = 'catalan';
    case CHINESE = 'chinese';
    case DANISH = 'danish';
    case DIVEHI = 'divehi';
    case DZONGKHA = 'dzongkha';
    case ESTONIAN = 'estonian';
    case FINNISH = 'finnish';
    case HAUSA = 'hausa';
    case HEBREW = 'hebrew';
    case ICELANDIC = 'icelandic';
    case INDONESIAN = 'indonesian';
    case JAPANESE = 'japanese';
    case KAZAKH = 'kazakh';
    case KHMER = 'khmer';
    case KIRGIZ = 'kirgiz';
    case KIRUNDI = 'kirundi';
    case KOREAN = 'korean';
    case LAO = 'lao';
    case LATVIAN = 'latvian';
    case LINGALA = 'lingala';
    case LITHUANIAN = 'lithuanian';
    case LUXEMBOURGISH = 'luxembourgish';
    case MALAGASY = 'malagasy';
    case MALAY = 'malay';
    case MALTESE = 'maltese';
    case MONGOLIAN = 'mongolian';
    case NEPALI = 'nepali';
    case NORWEGIAN = 'norwegian';
    case OSSETIAN = 'ossetian';
    case QUECHUA = 'quechua';
    case ROMANIAN = 'romanian';
    case ROMANSH = 'romansh';
    case SANGO = 'sango';
    case SERBIAN = 'serbian';
    case SHONA = 'shona';
    case SINHALA = 'sinhala';
    case SLOVENIAN = 'slovenian';
    case SOMALI = 'somali';
    case SWAHILI = 'swahili';
    case SWEDISH = 'swedish';
    case TAGALOG = 'tagalog';
    case TAJIK = 'tajik';
    case TAMIL = 'tamil';
    case THAI = 'thai';
    case TONGAN = 'tongan';
    case TURKISH = 'turkish';
    case TURKMEN = 'turkmen';
    case URDU = 'urdu';
    case UZBEK = 'uzbek';
    case VIETNAMESE = 'vietnamese';
    case YORUBA = 'yoruba';
    case ZULU = 'zulu';


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
            self::GREEK => 'gr',
            self::AFRIKAANS => 'na',
            self::ALBANIAN => 'al',
            self::ARABIC => 'ae',
            self::ARMENIAN => 'am',
            self::AZERBAIJANI => 'az',
            self::BELARUSIAN => 'by',
            self::BENGALI => 'bd',
            self::BULGARIAN => 'bg',
            self::BURMESE => 'mm',
            self::CATALAN => 'ad',
            self::CHINESE => 'sg',
            self::DANISH => 'dk',
            self::DIVEHI => 'mv',
            self::DZONGKHA => 'bt',
            self::ESTONIAN => 'ee',
            self::FINNISH => 'fi',
            self::HAUSA => 'ne',
            self::HEBREW => 'il',
            self::ICELANDIC => 'is',
            self::INDONESIAN => 'id',
            self::JAPANESE => 'jp',
            self::KAZAKH => 'kz',
            self::KHMER => 'kh',
            self::KIRGIZ => 'kg',
            self::KIRUNDI => 'bi',
            self::KOREAN => 'kr',
            self::LAO => 'la',
            self::LATVIAN => 'lv',
            self::LINGALA => 'cg',
            self::LITHUANIAN => 'lt',
            self::LUXEMBOURGISH => 'lu',
            self::MALAGASY => 'mg',
            self::MALAY => 'bn',
            self::MALTESE => 'mt',
            self::MONGOLIAN => 'mn',
            self::NEPALI => 'np',
            self::NORWEGIAN => 'no',
            self::OSSETIAN => 'ge',
            self::QUECHUA => 'bo',
            self::ROMANIAN => 'ro',
            self::ROMANSH => 'ch',
            self::SANGO => 'cf',
            self::SERBIAN => 'rs',
            self::SHONA => 'zw',
            self::SINHALA => 'lk',
            self::SLOVENIAN => 'si',
            self::SOMALI => 'so',
            self::SWAHILI => 'ke',
            self::SWEDISH => 'se',
            self::TAGALOG => 'ph',
            self::TAJIK => 'tj',
            self::TAMIL => 'my',
            self::THAI => 'th',
            self::TONGAN => 'to',
            self::TURKISH => 'tr',
            self::TURKMEN => 'tm',
            self::URDU => 'pk',
            self::UZBEK => 'uz',
            self::VIETNAMESE => 'vn',
            self::YORUBA => 'ng',
            self::ZULU => 'za',
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
            'gr' => self::GREEK,
            'na' => self::AFRIKAANS,
            'al' => self::ALBANIAN,
            'ae' => self::ARABIC,
            'am' => self::ARMENIAN,
            'az' => self::AZERBAIJANI,
            'by' => self::BELARUSIAN,
            'bd' => self::BENGALI,
            'bg' => self::BULGARIAN,
            'mm' => self::BURMESE,
            'ad' => self::CATALAN,
            'sg' => self::CHINESE,
            'dk' => self::DANISH,
            'mv' => self::DIVEHI,
            'bt' => self::DZONGKHA,
            'ee' => self::ESTONIAN,
            'fi' => self::FINNISH,
            'ne' => self::HAUSA,
            'il' => self::HEBREW,
            'is' => self::ICELANDIC,
            'id' => self::INDONESIAN,
            'jp' => self::JAPANESE,
            'kz' => self::KAZAKH,
            'kh' => self::KHMER,
            'kg' => self::KIRGIZ,
            'bi' => self::KIRUNDI,
            'kr' => self::KOREAN,
            'la' => self::LAO,
            'lv' => self::LATVIAN,
            'cg' => self::LINGALA,
            'lt' => self::LITHUANIAN,
            'lu' => self::LUXEMBOURGISH,
            'mg' => self::MALAGASY,
            'bn' => self::MALAY,
            'mt' => self::MALTESE,
            'mn' => self::MONGOLIAN,
            'np' => self::NEPALI,
            'no' => self::NORWEGIAN,
            'ge' => self::OSSETIAN,
            'bo' => self::QUECHUA,
            'ro' => self::ROMANIAN,
            'ch' => self::ROMANSH,
            'cf' => self::SANGO,
            'rs' => self::SERBIAN,
            'zw' => self::SHONA,
            'lk' => self::SINHALA,
            'si' => self::SLOVENIAN,
            'so' => self::SOMALI,
            'ke' => self::SWAHILI,
            'se' => self::SWEDISH,
            'ph' => self::TAGALOG,
            'tj' => self::TAJIK,
            'my' => self::TAMIL,
            'th' => self::THAI,
            'to' => self::TONGAN,
            'tr' => self::TURKISH,
            'tm' => self::TURKMEN,
            'pk' => self::URDU,
            'uz' => self::UZBEK,
            'vn' => self::VIETNAMESE,
            'ng' => self::YORUBA,
            'za' => self::ZULU,
            default => null,
        };
    }

    public static function getAvailableLanguages(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public function mapForbiddenLangToDefault(): self
    {
        return match($this) {
            self::RUSSIAN => self::ENGLISH,
            default => $this,
        };
    }
} 