<?php
/**
 * Class Languges
 */

namespace RW\Services;


class Languges
{
    /**
     * A list of supported languages
     * Most language code parameters conform to ISO-639-1 identifiers, except where noted.
     * @var array
     */
    public static $supportedLanguages = [
        "en" => ['gct' => "en", 'oht' => "en-us"],
        "af" => ['gct' => "af", 'oht' => "af"],
        "ar" => ['gct' => "ar", 'oht' => "ar-sa"],
        "bg" => ['gct' => "bg", 'oht' => "bg-bg"],
        "zh" => ['gct' => "zh", 'oht' => "zh-cn-cmn-s"],
        "zh-cn" => ['gct' => "zh-cn", 'oht' => "zh-cn-cmn-s"], //BCP-47
        "zh-tw" => ['gct' => "zh-tw", 'oht' => "zh-cn-cmn-t"], //BCP-47
        "hr" => ['gct' => "hr", 'oht' => "hr-hr"],
        "cs" => ['gct' => "cs", 'oht' => "cs-cz"],
        "da" => ['gct' => "da", 'oht' => "da-dk"],
        "nl" => ['gct' => "nl", 'oht' => "nl-nl"],
        "fr" => ['gct' => "fr", 'oht' => "fr-fr"],
        "de" => ['gct' => "de", 'oht' => "de-de"],
        "el" => ['gct' => "el", 'oht' => "el-gr"],
        "iw" => ['gct' => "iw"],
        "hi" => ['gct' => "hi", 'oht' => "hi-in"],
        "is" => ['gct' => "is", 'oht' => "is-is"],
        "id" => ['gct' => "id", 'oht' => "id-id"],
        "it" => ['gct' => "it", 'oht' => "it-it"],
        "ja" => ['gct' => "ja", 'oht' => "jp-jp"],
        "ko" => ['gct' => "ko", 'oht' => "ko-kp"],
        "no" => ['gct' => "no", 'oht' => "no-no"],
        "pl" => ['gct' => "pl", 'oht' => "pl-pl"],
        "pt" => ['gct' => "pt", 'oht' => "pt-pt"],
        "pt-br" => ['gct' => "pt", 'oht' => "pt-br"], //ISO 639.2
        "pt-pt" => ['gct' => "pt", 'oht' => "pt-pt"], //ISO 639.2
        "ro" => ['gct' => "ro", 'oht' => "ro-ro"],
        "ru" => ['gct' => "ru", 'oht' => "ru-ru"],
        "sk" => ['gct' => "sk", 'oht' => "sk-sk"],
        "es" => ['gct' => "es", 'oht' => "es-es"],
        "sv" => ['gct' => "sv", 'oht' => "sv-se"],
        "th" => ['gct' => "th", 'oht' => "th-th"],
        "tr" => ['gct' => "tr", 'oht' => "tr-tr"],
        "vi" => ['gct' => "vi", 'oht' => "vi-vn"],
        "fi" => ['oht' => "fi-fi"],
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
        if (empty(self::$supportedLanguages[$lang]['gct'])) {
           return false;
        }

        return self::$supportedLanguages[$lang]['gct'];
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
        if (empty(self::$supportedLanguages[$lang]['oht'])) {
            return false;
        }

        return self::$supportedLanguages[$lang]['oht'];
    }
}