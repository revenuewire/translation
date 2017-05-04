<?php
/**
 * Class GoogleCloudTranslation
 */

namespace Services;


class GoogleCloudTranslation
{
    /**
     * NMT supported languages
     *
     * @var array
     */
    public static $supportedLanguages = [
        "af",
        "ar",
        "bg",
        "zh",
        "hr",
        "cs",
        "da",
        "nl",
        "fr",
        "de",
        "el",
        "iw",
        "hi",
        "is",
        "id",
        "it",
        "ja",
        "ko",
        "no",
        "pl",
        "pt",
        "ro",
        "ru",
        "sk",
        "es",
        "sv",
        "th",
        "tr",
        "vi",
    ];

    /**
     * Get The language code match to the target provider.
     *
     * @param $lang
     *
     * @return mixed
     */
    public static function transformTargetLang($lang)
    {
        $lang = substr($lang, 0, 2);
        if (in_array($lang, self::$supportedLanguages)) {
            return $lang;
        }

        throw new \InvalidArgumentException("The target language is not supported by this provider.");
    }
}