<?php
namespace Services;
use com\OHT\API\OHTAPI;

/**
 * Class OneHourTranslation
 */
class OneHourTranslation
{
    /**
     * OHT supports ISO 639.2
     * @var array
     */
    public static $supportedLanguages = [
        "af",
        "sq-al",
        "am-et",
        "ar-sa",
        "ar-eg",
        "ar-ae",
        "ar-jo",
        "ar-ma",
        "hy-am",
        "az-az",
        "baq",
        "be-by",
        "bn-bd",
        "bs-ba",
        "bg-bg",
        "my-mm",
        "ca-es",
        "zh-cn-yue",
        "zh-cn-cmn-s",
        "zh-cn-cmn-t",
        "zh-tw",
        "hr-hr",
        "cs-cz",
        "da-dk",
        "fa-af",
        "nl-nl",
        "en-us",
        "et-ee",
        "fi-fi",
        "fl-be",
        "fr-fr",
        "fr-ca",
        "ka-ge",
        "de-de",
        "el-gr",
        "gu-in",
        "ht",
        "he-il",
        "hi-in",
        "hu-hu",
        "is-is",
        "id-id",
        "ga-ie",
        "it-it",
        "jp-jp",
        "kn",
        "kk-kz",
        "km-kh",
        "ko-kp",
        "ku-tr",
        "lo-la",
        "lv-lv",
        "lt-lt",
        "mk-mk",
        "ms-my",
        "ml-in",
        "mt-mt",
        "mr-in",
        "mn-mn",
        "sr-me",
        "ne-np",
        "no-no",
        "ps",
        "fa-ir",
        "pl-pl",
        "pt-br",
        "pt-pt",
        "pa-in",
        "ro-ro",
        "ru-ru",
        "sr-rs",
        "sr",
        "zn-shn",
        "si",
        "sk-sk",
        "sl-si",
        "so-so",
        "es-es",
        "es-ar",
        "sw",
        "sv-se",
        "fr-ch",
        "gsw-ch",
        "it-ch",
        "tl-ph",
        "tgk",
        "ta-in",
        "te-in",
        "th-th",
        "tir",
        "tr-tr",
        "uk-ua",
        "ur",
        "uz-uz",
        "vi-vn",
        "cy-bg",
        "xho",
        "yo-ng",
        "zul",
    ];

    /**
     * ISO 639.1 map
     * @var array
     */
    public static $lang639_1 = [
        "af" => "af",
        "sq" => "sq-al",
        "am" => "am-et",
        "ar" => "ar-sa",
        "hy" => "hy-am",
        "az" => "az-az",
        "baq" => "baq",
        "be" => "be-by",
        "bn" => "bn-bd",
        "bs" => "bs-ba",
        "bg" => "bg-bg",
        "my" => "my-mm",
        "ca" => "ca-es",
        "zh" => "zh-cn-cmn-s",
        "hr" => "hr-hr",
        "cs" => "cs-cz",
        "da" => "da-dk",
        "nl" => "nl-nl",
        "en" => "en-us",
        "et" => "et-ee",
        "fi" => "fi-fi",
        "fl" => "fl-be",
        "fr" => "fr-fr",
        "ka" => "ka-ge",
        "de" => "de-de",
        "el" => "el-gr",
        "gu" => "gu-in",
        "ht" => "ht",
        "he" => "he-il",
        "hi" => "hi-in",
        "hu" => "hu-hu",
        "is" => "is-is",
        "id" => "id-id",
        "ga" => "ga-ie",
        "it" => "it-it",
        "jp" => "jp-jp",
        "kn" => "kn",
        "kk" => "kk-kz",
        "km" => "km-kh",
        "ko" => "ko-kp",
        "ku" => "ku-tr",
        "lo" => "lo-la",
        "lv" => "lv-lv",
        "lt" => "lt-lt",
        "mk" => "mk-mk",
        "ms" => "ms-my",
        "ml" => "ml-in",
        "mt" => "mt-mt",
        "mr" => "mr-in",
        "mn" => "mn-mn",
        "ne" => "ne-np",
        "no" => "no-no",
        "ps" => "ps",
        "fa" => "fa-ir",
        "pl" => "pl-pl",
        "pt-br" => "pt-br",
        "pt-pt" => "pt-pt",
        "pa-in" => "pa-in",
        "ro-ro" => "ro-ro",
        "ru-ru" => "ru-ru",
        "sr" => "sr",
        "zn" => "zn-shn",
        "si" => "si",
        "sk" => "sk-sk",
        "sl" => "sl-si",
        "so" => "so-so",
        "es" => "es-es",
        "sw" => "sw",
        "sv" => "sv-se",
        "gsw" => "gsw-ch",
        "tl" => "tl-ph",
        "tgk" => "tgk",
        "ta" => "ta-in",
        "te" => "te-in",
        "th" => "th-th",
        "tir" => "tir",
        "tr" => "tr-tr",
        "uk" => "uk-ua",
        "ur" => "ur",
        "uz" => "uz-uz",
        "vi" => "vi-vn",
        "cy" => "cy-bg",
        "xho" => "xho",
        "yo" => "yo-ng",
        "zul" => "zul",
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
        if (in_array($lang, self::$supportedLanguages)) {
            return $lang;
        }

        if (!empty(self::$lang639_1[$lang])) {
            return self::$lang639_1[$lang];
        }

        throw new \InvalidArgumentException("The target language is not supported by this provider.");
    }

    /** @var $oht \com\OHT\API\OHTAPI */
    public $oht;

    /**
     * OneHourTranslation constructor.
     *
     * @param $ohtPublicKey
     * @param $ohtSecretKey
     * @param bool $sandbox
     */
    function __construct($ohtPublicKey, $ohtSecretKey, $sandbox = true)
    {
        $this->oht = new OHTAPI($ohtPublicKey, $ohtSecretKey, $sandbox);
    }

    /**
     * Upload Resource Text
     *
     * @param $text
     *
     * @return \com\OHT\API\stdClass
     */
    function uploadResourceText($text)
    {
        $result = $this->oht->uploadTextResource($text);
        if (!empty($result->status->msg) && $result->status->msg == 'ok') {
            return $result->results[0];
        }
        throw new \InvalidArgumentException("Unable to upload resource to OHT. Reason: " . var_export($result, true));
    }

    /**
     * Create Project
     *
     * @param $targetLang
     * @param $sources
     */
    function createProject($name, $sourceLang, $targetLang, $sources, $expertise = null, $callbackURL = "", $note = "", $wordCount = 0)
    {
        $params = array();
        if (!empty($expertise)) {
            $params['expertise'] = $expertise;
        }

        if (!empty($name)) {
            $params['name'] = $name;
        }

        $result = $this->oht->newTranslationProject($sourceLang, $targetLang, implode(',',$sources), $wordCount, $note, $callbackURL, $params);
        if (!empty($result->status->msg) && $result->status->msg == 'ok') {
            return $result->results;
        }
        throw new \InvalidArgumentException("Unable to create project in OHT. Reason: " . var_export($result, true));
    }

}