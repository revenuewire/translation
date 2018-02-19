<?php
namespace RW;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Predis\Client;
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
    const keyLength = 50;

    public $dynamoSettings = array();
    protected $db = null;
    protected $marshaler;
    public $supportLanguages;

    /** @var $cache Client  */
    public $cache = null;
    public $cachePrefix = null;
    public $defaultLang = Languages::DEFAULT_LANGUAGE_CODE;

    public $live = false;
    public $excludeFromLiveTranslation = [];

    public $namespace = "";

    /**
     * Translation constructor.
     *
     * @param null $dynamoSettings
     * @param array $supportLanguages
     * @param null $defaultLang
     * @param null $gct
     * @param array $excludeFromLiveTranslation
     * @param string $namespace
     */
    function __construct($dynamoSettings = null,
                         $supportLanguages = ['en'],
                         $defaultLang = null,
                         $gct = null,
                         $excludeFromLiveTranslation = [],
                         $namespace = "")
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
        $this->excludeFromLiveTranslation = $excludeFromLiveTranslation;

        /**
         * Using DynamoDB as storage of the translation
         */
        if (!empty($dynamoSettings) && !empty($dynamoSettings['region'])
            && !empty($dynamoSettings['version']) && !empty($dynamoSettings['table'])) {
            /** @var $db DynamoDbClient */
            $this->db = new DynamoDbClient($dynamoSettings);
            $this->table = $dynamoSettings['table'];
        }

        /** @var marshaler Marshaler */
        $this->marshaler = new Marshaler();

        /**
         * Using Cache as storage of the translation
         */
        if (!empty($cache) && !empty($cache['host']) && !empty($cache['timeout']) && !empty($cache['port'])) {
            $options = ['cluster' => 'redis'];
            $this->cache = new Client(array(
                'scheme'   => 'tcp',
                'host'     => $cache['host'],
                'timeout'  => $cache['timeout'],
                'port'     => $cache['port'],
            ), $options);
            if (!empty($cache['prefix'])) {
                $this->cachePrefix = $cache['prefix'];
            }
        }

        if ($this->live == true) {
            $languages = array_diff($supportLanguages, $excludeFromLiveTranslation);
            //check if we can support the given languages
            foreach ($languages as $language) {
                if (Languages::transformLanguageCodeToGTC($language) === false){
                    throw new \InvalidArgumentException("Unable to support the language translation in live mode. [$language].");
                }
            }
        }

        if ($this->db === null && !empty($excludeFromLiveTranslation)) {
            //effectively disabled the translation
            $this->supportLanguages = [];
        }

        /**
         * If live mode is true, and both db and cache are not available, then disable the translation
         */
        if ($this->live == true && $this->db === null) {
            $this->supportLanguages = [];
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
     * @param null $dynamoSettings
     * @param array $supportLanguages
     * @param null $defaultLang
     * @param null $gct
     * @param array $excludeFromLiveTranslation
     * @param string $namespace
     * @return null|Translation
     */
    public static function init($dynamoSettings = null,
                                $supportLanguages = ['en'],
                                $defaultLang = null,
                                $gct = null,
                                $excludeFromLiveTranslation = [],
                                $namespace = "")
    {
        self::$translator = new Translation($dynamoSettings, $supportLanguages, $defaultLang, $gct, $excludeFromLiveTranslation, $namespace);
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

        /**
         * If language is not in supported languages, just return original text back
         */
        $lang = Languages::transformLanguageCode($lang);
        if (!in_array($lang, $this->supportLanguages)) {
            return $messages;
        }

        $batchKeys = [];
        $slugTextIdMap = [];
        $slugTextIdMapReversed = [];
        foreach ($messages as $textId => $text) {
            $text = trim($text);
            if (empty($text)) {
                throw new \InvalidArgumentException("Text cannot be empty.");
            }
            $id = \RW\Models\Translation::idFactory($lang, $text, $this->namespace);
            if (empty($slugTextIdMap[$id])) {
                $batchKeys[] = ['id' => $this->marshaler->marshalValue($id)];
                $slugTextIdMap[$id] = $textId;
            }
            $slugTextIdMapReversed[$textId] = $id;
        }

        $batchChunks = array_chunk($batchKeys, 50);
        $resultMessages = [];
        foreach ($batchChunks as $chunk) {
            $results = $this->db->batchGetItem([
                'RequestItems' => [
                    $this->table => [
                        "Keys" => $chunk,
                        'ConsistentRead' => false,
                        'ProjectionExpression' => 'id, t',
                    ]
                ]
            ]);

            foreach ($results['Responses'][$this->table] as $response) {
                $id = $response['id']['S'];
                $text = $response['t']['S'];
                $resultMessages[$slugTextIdMap[$id]] = $text;
            }
        }

        $missingMessages = array_diff($messages, $resultMessages);
        if ($lang == $this->defaultLang) {
            /**
             * If there are missing messages, wrote down to db, and return the batch
             */
            $batchData = [];
            foreach ($missingMessages as $k => $v) {
                $id = $slugTextIdMapReversed[$k];
                $item = [
                    'id' => $id,
                    't' => $v,
                    'l' => $lang,
                ];
                if (!empty($this->namespace)) {
                    $item['n'] = $this->namespace;
                }
                $batchData[$id] = ['PutRequest' => [
                    "Item" => $this->marshaler->marshalItem($item)
                ]];
            }
            if (count($batchData) > 0) {
                $batchChunks = array_chunk($batchData, 25);
                foreach ($batchChunks as $chunk) {
                    $this->db->batchWriteItem([
                        'RequestItems' => [
                            $this->table => $chunk
                        ]
                    ]);
                }
            }

            return $messages;
        }

        /**
         * If it is live mode, translated and put it into cache
         */
        if ($this->live === true && !in_array($lang, $this->excludeFromLiveTranslation)) {
            $sourceLang = Languages::transformLanguageCodeToGTC($this->defaultLang);
            $targetLang = Languages::transformLanguageCodeToGTC($lang);
            $translatedMessages = GoogleCloudTranslation::batchTranslate($sourceLang, $targetLang, $missingMessages);

            $batchData = [];
            foreach ($translatedMessages as $k => $v) {
                $id = $slugTextIdMapReversed[$k];
                $item = [
                    'id' => $id,
                    't' => $v,
                    'l' => $lang,
                ];
                if (!empty($this->namespace)) {
                    $item['n'] = $this->namespace;
                }
                $batchData[$id] = ['PutRequest' => [
                    "Item" => $this->marshaler->marshalItem($item)
                ]];
            }
            if (count($batchData) > 0) {
                $batchChunks = array_chunk($batchData, 25);
                foreach ($batchChunks as $chunk) {
                    $this->db->batchWriteItem([
                        'RequestItems' => [
                            $this->table => $chunk
                        ]
                    ]);
                }
            }

            return array_merge($resultMessages,$translatedMessages);
        }

        /**
         * If we end up nowhere, just return the original batch
         */
        return $messages;
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
        $id = \RW\Models\Translation::idFactory($lang, $text, $this->namespace);

        /**
         * If it is not the default language
         */
        $result = $this->db->getItem(array(
            'TableName' => $this->table,
            'Key' => array(
                'id' => array('S' => $id)
            ),
            'ConsistentRead' => false
        ));

        if (empty($result['Item'])) {
            /**
             * If it is the default language and we missed the db hit, this means it is the first time we had this text
             */
            if ($lang == $this->defaultLang) {
                $data = [
                    'id' => $id,
                    't' => $text,
                    'l' => $lang,
                ];
                if (!empty($this->namespace)) {
                    $data['n'] = $this->namespace;
                }
                $this->db->putItem(array(
                    'TableName' => $this->table,
                    'Item' => $this->marshaler->marshalItem($data),
                    'ReturnValues' => 'ALL_OLD'
                ));

                return $text;
            }

            /**
             * If live mode is enabled, let's translate it and save it to db
             */
            if ($this->live === true && !in_array($lang, $this->excludeFromLiveTranslation)) {
                $sourceLang = Languages::transformLanguageCodeToGTC($this->defaultLang);
                $targetLang = Languages::transformLanguageCodeToGTC($lang);
                $translatedText = GoogleCloudTranslation::translate($sourceLang, $targetLang, $text);

                $data = [
                    'id' => $id,
                    't' => $translatedText,
                    'l' => $lang,
                ];
                if (!empty($this->namespace)) {
                    $data['n'] = $this->namespace;
                }
                $this->db->putItem(array(
                    'TableName' => $this->table,
                    'Item' => $this->marshaler->marshalItem($data),
                    'ReturnValues' => 'ALL_OLD'
                ));

                return $translatedText;
            }

            return $text;
        }

        $data = $this->marshaler->unmarshalItem($result['Item']);

        return $data['t'];

    }
}