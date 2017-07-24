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
        try {
            $translation = self::$client->translate($text, [
                'source ' => $sourceLanguage,
                'target' => $targetLanguage,
                "model" => "nmt",
                'format' => 'text'
            ]);
            return $translation['text'];
        } catch (\Exception $e) {
            return $text;
        }
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
        $messages = [];
        $batchChunks = array_chunk($texts, 10, true);

        foreach ($batchChunks as $batchChunk) {
            $keys = array_keys($batchChunk);

            try {
                $translations = self::$client->translateBatch(array_values($batchChunk), [
                    'source ' => $sourceLanguage,
                    'target' => $targetLanguage,
                    "model" => "nmt",
                    'format' => 'text'
                ]);
                $values = array_column($translations, "text");

                $messages = array_merge($messages, array_combine($keys, $values));
            }catch (\Exception $e) {
                continue;
            }
        }

        return $messages;
    }
}