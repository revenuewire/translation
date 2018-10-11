<?php
namespace RW;

use RW\Services\GoogleCloudTranslation;
use RW\Services\Languages;

class Translation
{
    public static $userLanguage = Languages::DEFAULT_LANGUAGE_CODE;
    public static $translator = null;

    /**
     * The max length you want to put for key. Note: If the key length is very small, you might run into collision.
     * If it is too big, you wasted space.
     */
    const MAX_KEY_LENGTH = 50;
    public $supportLanguages;
    public $defaultLang = Languages::DEFAULT_LANGUAGE_CODE;
    public $live = false;
    public $namespace = "";
    const CACHE_KEY = "t_sxs3_";

    /**
     * ID factory
     *
     * @param $lang
     * @param $text
     *
     * @return string
     */
    public static function idFactory($lang, $text, $namespace = "")
    {
        $text = trim($text);
        $namespace = Utils::slugify($namespace);
        $id = strlen($text) > self::MAX_KEY_LENGTH ?
            substr($text, 0, self::MAX_KEY_LENGTH) . hash('crc32', substr($text, self::MAX_KEY_LENGTH+1))
            : $text;

        return hash('ripemd160', implode('|:|', array($lang, $id, $namespace)));
    }

    /**
     * Translation constructor.
     *
     * @param array $supportLanguages
     * @param null $defaultLang
     * @param null $gct
     * @param string $namespace
     */
    function __construct($supportLanguages = ['en'], $defaultLang = null, $gct = null, $namespace = "")
    {
        if (!empty($defaultLang)) {
            $this->defaultLang = $defaultLang;
        }

        //add namespace
        $this->namespace = $namespace;

        //check if we can support the given languages
        foreach ($supportLanguages as $language) {
            if (Languages::transformLanguageCodeToGTC($language) === false
                && Languages::transformLanguageCodeToOTH($language) === false) {
                throw new \InvalidArgumentException("Unable to support the language translation [$language].");
            }
        }
        $this->supportLanguages = $supportLanguages;

        /**
         * Using Live Google Cloud Translation
         */
        if (!empty($gct) && !empty($gct['project']) && !empty($gct['key'])) {
            GoogleCloudTranslation::init($gct['project'], $gct['key']);
            $this->live = true;
        }

        if ($this->live == true) {
            //check if we can support the given languages
            foreach ($supportLanguages as $language) {
                if (Languages::transformLanguageCodeToGTC($language) === false){
                    throw new \InvalidArgumentException("Unable to support the language translation in live mode. [$language].");
                }
            }
        }
    }

    /**
     * Get Static function.
     *
     * @return null
     * @throws \Exception
     */
    public static function getInstance()
    {
        if (self::$translator instanceof Translation) {
            return self::$translator;
        }

        throw new \Exception("Translation object has not be initialized.");
    }

    /**
     * Init the translation service
     *
     * @param array $supportLanguages
     * @param null $defaultLang
     * @param null $gct
     * @param string $namespace
     * @return null|Translation
     */
    public static function init($supportLanguages = ['en'], $defaultLang = null, $gct = null, $namespace = "")
    {
        self::$translator = new Translation($supportLanguages, $defaultLang, $gct, $namespace);
        return self::$translator;
    }

    /**
     * Batch Translate
     *
     * @param array $messages
     * @param string $lang
     *
     * @return array
     */
    public function batchTranslate($messages = array(), $lang = null)
    {
        if (empty($lang)) {
            $lang = $this->defaultLang;
        }

        if (empty($messages)) {
            return $messages;
        }

        if ($lang === $this->defaultLang) {
            return $messages;
        }

        /**
         * If language is not in supported languages, just return original text back
         */
        $lang = Languages::transformLanguageCode($lang);
        if (!in_array($lang, $this->supportLanguages)) {
            return $messages;
        }

        $slugTextIdMap = [];
        $missingMessages = [];
        $translatedMessages = [];
        foreach ($messages as $textId => $text) {
            $text = trim($text);
            if (empty($text)) {
                throw new \InvalidArgumentException("Text cannot be empty.");
            }
            $id = self::idFactory($lang, $text, $this->namespace);
            $cacheKey = self::CACHE_KEY . $id;

            if (empty($slugTextIdMap[$id])) {
                $slugTextIdMap[$id] = $textId;
            }
            if (!apcu_exists($cacheKey)) {
                $missingMessages[$id] = $text;
            } else {
                $translatedText = apcu_fetch($cacheKey);
                $translatedMessages[$slugTextIdMap[$id]] = $translatedText;
            }
        }

        if (count($missingMessages) > 0) {
            $sourceLang = Languages::transformLanguageCodeToGTC($this->defaultLang);
            $targetLang = Languages::transformLanguageCodeToGTC($lang);
            $translatedMissingMessages = GoogleCloudTranslation::batchTranslate($sourceLang, $targetLang, $missingMessages);
            foreach ($translatedMissingMessages as $id => $translatedText) {
                $cacheKey = self::CACHE_KEY . $id;
                apcu_store($cacheKey, $translatedText);
                $translatedMessages[$slugTextIdMap[$id]] = $translatedText;
            }
        }

        /**
         * If we end up nowhere, just return the original batch
         */
        return array_replace($messages, $translatedMessages);
    }

    /**
     * Translate a single text
     *
     * @param $text
     * @param string $lang
     *
     * @return string
     */
    public function translate($text, $lang = null)
    {
        if (empty($lang)) {
            $lang = $this->defaultLang;
        }

        if ($lang === $this->defaultLang) {
            return $text;
        }

        /**
         * If the text is empty, or if language is English return it with empty string.
         */
        $text = trim($text);
        if (empty($text)) {
            return $text;
        }

        /**
         * If language is not in supported languages, just return original text back
         */
        $lang = Languages::transformLanguageCode($lang);
        if (!in_array($lang, $this->supportLanguages)) {
            return $text;
        }

        /**
         * Always check cache first
         */
        $id = self::idFactory($lang, $text, $this->namespace);
        $cacheKey = self::CACHE_KEY . $id;
        if (apcu_exists($cacheKey)) {
            return apcu_fetch($cacheKey);
        }

        $sourceLang = Languages::transformLanguageCodeToGTC($this->defaultLang);
        $targetLang = Languages::transformLanguageCodeToGTC($lang);
        $translatedText = GoogleCloudTranslation::translate($sourceLang, $targetLang, $text);

        if (!empty($translatedText)) {
            apcu_store($cacheKey, $translatedText);
            return $translatedText;
        }

        return $text;
    }
}