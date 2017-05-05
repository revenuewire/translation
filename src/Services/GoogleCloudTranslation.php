<?php
/**
 * Class GoogleCloudTranslation
 */

namespace RW\Services;


use Google\Cloud\Translate\TranslateClient;

class GoogleCloudTranslation
{
    /** @var $client TranslateClient */
    public static $client;

    /**
     * NMT supported languages
     *
     * @var array
     */
    public static $supportedLanguages = [
        "en",
        "af",
        "ar",
        "bg",
        "zh-CN",
        "zh-TW",
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
     * Init Google Cloud Translation
     *
     * @param $projectId
     * @param $key
     */
    public static function init($projectId, $key)
    {
        self::$client = new TranslateClient([
            'projectId' => $projectId,
            'key' => $key
        ]);
    }

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
        //in case client uses ISO 639.2 format
        $lang = substr($lang, 0, 2);
        if ($lang == "zh") {
            return "zh-CN";
        }

        if (in_array($lang, self::$supportedLanguages)) {
            return $lang;
        }

        throw new \InvalidArgumentException("The target language is not supported by this provider. [$lang]");
    }

    /**
     * Translate
     *
     * @param $sourceLanguage
     * @param $targetLanguage
     * @param $text
     *
     * @return mixed
     */
    public static function translate($sourceLanguage, $targetLanguage, $text)
    {
        $translation = self::$client->translate($text, [
            'source ' => $sourceLanguage,
            'target' => $targetLanguage,
            "model" => "nmt"
        ]);
        return $translation['text'];
    }

    /**
     * Batch Translation
     *
     * @param $sourceLanguage
     * @param $targetLanguage
     * @param $texts
     *
     * @return array
     */
    public static function batchTranslate($sourceLanguage, $targetLanguage, $texts)
    {
        $keys = array_keys($texts);

        $translations = self::$client->translateBatch(array_values($texts), [
            'source ' => $sourceLanguage,
            'target' => $targetLanguage,
            "model" => "nmt"
        ]);
        $values = array_column($translations, "text");

        return array_combine($keys, $values);
    }
}