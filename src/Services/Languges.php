<?php
/**
 * Class Languges
 */

namespace RW\Services;


class Languges
{
    const DEFAULT_LANGUAGE_CODE = "en";

    /**
     * A list of supported languages
     * Most language code parameters conform to ISO-639-1 identifiers, except where noted.
     * @var array
     */
    public static $supportedLanguages = [
        "en" => [
            "language" => "English",
            "display" => "English",
            "providers" => ['gct' => "en", 'oht' => "en-us"]
        ],
        "af" => [
            "language" => "Afrikaans",
            "display" => "Afrikaans",
            "providers" => ['gct' => "af", 'oht' => "af"],
        ],
        "ar" => [
            "language" => "Arabic",
            "display" => "العربية",
            "providers" =>['gct' => "ar", 'oht' => "ar-sa"],
        ],
        "bg" => [
            "language" => "Bulgarian",
            "display" => "Български",
            "providers" =>['gct' => "bg", 'oht' => "bg-bg"],
        ],
        "zh" => [
            "language" => "Chinese (Simple)",
            "display" => "简体中文",
            "providers" =>['gct' => "zh", 'oht' => "zh-cn-cmn-s"],
        ],
        "zh-cn" => [
            "language" => "Chinese (Simple)",
            "display" => "简体中文",
            "providers" =>['gct' => "zh-cn", 'oht' => "zh-cn-cmn-s"],
            "comments" => "BCP-47",
        ],
        "zh-tw" => [
            "language" => "Chinese (Traditional)",
            "display" => "繁體中文",
            "providers" =>['gct' => "zh-tw", 'oht' => "zh-cn-cmn-t"],
            "comments" => "BCP-47",
        ],
        "hr" => [
            "language" => "Croatian",
            "display" => "hrvatski",
            "providers" =>['gct' => "hr", 'oht' => "hr-hr"],
        ],
        "cs" => [
            "language" => "Czech",
            "display" => "český",
            "providers" =>['gct' => "cs", 'oht' => "cs-cz"],
        ],
        "da" => [
            "language" => "Danish",
            "display" => "dansk",
            "providers" =>['gct' => "da", 'oht' => "da-dk"],
        ],
        "nl" => [
            "language" => "Dutch",
            "display" => "Nederlands",
            "providers" =>['gct' => "nl", 'oht' => "nl-nl"],
        ],
        "fr" => [
            "language" => "French",
            "display" => "français",
            "providers" =>['gct' => "fr", 'oht' => "fr-fr"],
        ],
        "de" => [
            "language" => "German",
            "display" => "Deutsch",
            "providers" =>['gct' => "de", 'oht' => "de-de"],
        ],
        "el" => [
            "language" => "Greek",
            "display" => "ελληνικά",
            "providers" =>['gct' => "el", 'oht' => "el-gr"],
        ],
        "iw" => [
            "language" => "Hebrew",
            "display" => "עברית",
            "providers" =>['gct' => "iw"],
        ],
        "hi" => [
            "language" => "Hindi",
            "display" => "हिन्दी",
            "providers" =>['gct' => "hi", 'oht' => "hi-in"],
        ],
        "is" => [
            "language" => "Icelandic",
            "display" => "íslenska",
            "providers" =>['gct' => "is", 'oht' => "is-is"],
        ],
        "id" => [
            "language" => "Indonesian",
            "display" => "Bahasa Indonesia",
            "providers" =>['gct' => "id", 'oht' => "id-id"],
        ],
        "it" => [
            "language" => "Italian",
            "display" => "italiano",
            "providers" =>['gct' => "it", 'oht' => "it-it"],
        ],
        "ja" => [
            "language" => "Japanese",
            "display" => "日本語",
            "providers" =>['gct' => "ja", 'oht' => "jp-jp"],
        ],
        "ko" => [
            "language" => "Korean",
            "display" => "한국어",
            "providers" =>['gct' => "ko", 'oht' => "ko-kp"],
        ],
        "no" => [
            "language" => "Norwegian",
            "display" => "Norsk",
            "providers" =>['gct' => "no", 'oht' => "no-no"],
        ],
        "pl" => [
            "language" => "Polish",
            "display" => "polski",
            "providers" =>['gct' => "pl", 'oht' => "pl-pl"],
        ],
        "pt" => [
            "language" => "Portuguese",
            "display" => "português",
            "providers" =>['gct' => "pt", 'oht' => "pt-pt"],
        ],
        "pt-br" => [
            "language" => "Portuguese (Brazil)",
            "display" => "português - Brasil",
            "providers" =>['oht' => "pt-br"],
            "comments" => "ISO 639.2",
        ],
        "pt-pt" => [
            "language" => "Portuguese (Portugal)",
            "display" => "português",
            "providers" =>['gct' => "pt", 'oht' => "pt-pt"],
            "comments" => "ISO 639.2",
        ],
        "ro" => [
            "language" => "Romanian",
            "display" => "limba română ",
            "providers" =>['gct' => "ro", 'oht' => "ro-ro"],
        ],
        "ru" => [
            "language" => "Russian",
            "display" => "Русский",
            "providers" =>['gct' => "ru", 'oht' => "ru-ru"],
        ],
        "sk" => [
            "language" => "Slovak",
            "display" => "slovenčina",
            "providers" =>['gct' => "sk", 'oht' => "sk-sk"],
        ],
        "es" => [
            "language" => "Spanish",
            "display" => "español",
            "providers" =>['gct' => "es", 'oht' => "es-es"],
        ],
        "sv" => [
            "language" => "Swedish",
            "display" => "svenska",
            "providers" =>['gct' => "sv", 'oht' => "sv-se"],
        ],
        "th" => [
            "language" => "Thai",
            "display" => "ภาษาไทย",
            "providers" =>['gct' => "th", 'oht' => "th-th"],
        ],
        "tr" => [
            "language" => "Turkish",
            "display" => "Türkçe",
            "providers" =>['gct' => "tr", 'oht' => "tr-tr"],
        ],
        "vi" => [
            "language" => "Vietnamese",
            "display" => "Tiếng Việt",
            "providers" =>['gct' => "vi", 'oht' => "vi-vn"],
        ],
        "fi" => [
            "language" => "Finnish",
            "display" => "suomi",
            "providers" =>['oht' => "fi-fi"],
        ],
    ];

    /**
     * Try to transform language to supported language
     *
     * @param $lang
     *
     * @return mixed
     */
    public static function transformLanguageCode($lang)
    {
        //great, find it
        if (!empty(self::$supportedLanguages[$lang])) {
            return $lang;
        }

        //because most of the support code is in ISO-639-1
        $iso6391 = substr($lang, 0, 2);
        if (!empty(self::$supportedLanguages[$iso6391])) {
            return $iso6391;
        }

        return $lang;
    }

    /**
     * Transform language to GCT
     *
     * @param $lang
     *
     * @return mixed
     */
    public static function transformLanguageCodeToGTC($lang)
    {
        if (empty(self::$supportedLanguages[$lang]['providers']['gct'])) {
           return false;
        }

        return self::$supportedLanguages[$lang]['providers']['gct'];
    }

    /**
     * Transform language to OTH
     *
     * @param $lang
     *
     * @return mixed
     */
    public static function transformLanguageCodeToOTH($lang)
    {
        if (empty(self::$supportedLanguages[$lang]['providers']['oht'])) {
            return false;
        }

        return self::$supportedLanguages[$lang]['providers']['oht'];
    }

    /**
     * Get Browser Language
     *
     * @return bool|string
     */
    public static function getBrowserLanguage()
    {
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $locale = \Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            if ($locale == 'zh') {
                $locales = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
                $locale = array_shift($locales);
            }
            $locale = str_replace('_', '-', strtolower($locale));
            return $locale;
        }

        return self::DEFAULT_LANGUAGE_CODE;
    }

    /**
     * Get Markdown Format
     */
    public static function getLanguagesMarkdown()
    {
        $mark = [
            "| Code | Google Cloud Translation	| One Hour Translation | Language |	Display | Note |",
            "| ----- |:------:|:----------:|:---------:|:---------:|:--------:|",
        ];
        foreach (self::$supportedLanguages as $k => $v) {
            $mark[] = "| " . implode(' | ', [
                $k,
                isset($v['providers']['gct']) ? $v['providers']['gct'] : "N/A",
                isset($v['providers']['oht']) ? $v['providers']['oht'] : "N/A",
                $v['language'],
                $v['display'],
                !empty($v['comments']) ? $v['comments'] : "",
            ]) . " | ";
        }
        return implode("\n", $mark);
    }

    /**
     * Get Supported Languages Display
     * @return array
     */
    public static function getSupportedLanguagesDisplayMap()
    {
        $map = [];
        foreach (self::$supportedLanguages as $languageCode => $language) {
            $map[$languageCode] = $language['display'];
        }
        ksort($map);
        return $map;
    }

    /**
     * Get Supported Languages
     * @return array
     */
    public static function getSupportedLanguagesMap()
    {
        $map = [];
        foreach (self::$supportedLanguages as $languageCode => $language) {
            $map[$languageCode] = $language['language'];
        }
        ksort($map);
        return $map;
    }
}