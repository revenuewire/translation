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
     * @param null $cache
     * @param null $defaultLang
     * @param null $gct
     * @param array $excludeFromLiveTranslation
     * @param string $namespace
     */
    function __construct($dynamoSettings = null,
                         $supportLanguages = ['en'],
                         $cache = null,
                         $defaultLang = null,
                         $gct = null,
                         $excludeFromLiveTranslation = [], $namespace = "")
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

        if ($this->live == true && $this->cache === null) {
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
     * @param null $cache
     * @param null $defaultLang
     * @param null $gct
     * @param array $excludeFromLiveTranslation
     * @param string $namespace
     * @return null|Translation
     */
    public static function init($dynamoSettings = null,
                                $supportLanguages = ['en'],
                                $cache = null,
                                $defaultLang = null,
                                $gct = null,
                                $excludeFromLiveTranslation = [],
                                $namespace = "")
    {
        self::$translator = new Translation($dynamoSettings, $supportLanguages, $cache, $defaultLang, $gct, $excludeFromLiveTranslation, $namespace);
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

        /**
         * If perfect cache hits, yeah~~
         */
        if ($this->cache !== null) {
            $cacheKeys = array_keys($slugTextIdMap);
            $cachedResults = $this->getCacheMulti($cacheKeys);
            $cachedMessages = array_combine(array_values($slugTextIdMap), $cachedResults);

            $cacheKeysCount = count($cacheKeys);
            $cachedResultsCount = count(array_filter($cachedResults));

            if ($cachedResultsCount > 0 && $cacheKeysCount == $cachedResultsCount) {
                return $cachedMessages;
            }
        }

        /**
         * If it is live mode, translated and put it into cache
         */
        if ($this->live === true && !in_array($lang, $this->excludeFromLiveTranslation)) {
            if ($lang == $this->defaultLang) {
                return $messages;
            }
            $sourceLang = Languages::transformLanguageCodeToGTC($this->defaultLang);
            $targetLang = Languages::transformLanguageCodeToGTC($lang);
            $translatedMessages = GoogleCloudTranslation::batchTranslate($sourceLang, $targetLang, $messages);

            $this->setCacheBatch($translatedMessages, $slugTextIdMapReversed);
            return $translatedMessages;
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
        $resultMessages = array_merge($messages, $resultMessages);
        if ($lang == $this->defaultLang) {
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
            $this->setCacheBatch($resultMessages, $slugTextIdMapReversed);
        } else {
            $this->setCacheBatch($resultMessages, $slugTextIdMapReversed, 3600);
        }

        return $resultMessages;
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
        if ($this->hasCache($id)) {
            return $this->getCache($id);
        }

        /**
         * If it is live mode, translated and put it into cache
         */
        if ($this->live === true && !in_array($lang, $this->excludeFromLiveTranslation)) {
            if ($lang == $this->defaultLang) {
                return $text;
            }
            $sourceLang = Languages::transformLanguageCodeToGTC($this->defaultLang);
            $targetLang = Languages::transformLanguageCodeToGTC($lang);
            $translatedText = GoogleCloudTranslation::translate($sourceLang, $targetLang, $text);
            $this->setCache($id, $translatedText);

            return $translatedText;
        }

        /**
         * If it is not the default language, and we missed the cache
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
             * If it is the default language and we missed the cache, this means it is the first time we had this text
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

                $this->setCache($id, $text);

                return $text;
            }

            $this->setCache($id, $text, 3600);
            return $text;
        }

        $data = $this->marshaler->unmarshalItem($result['Item']);
        $this->setCache($id, $data['t']);

        return $data['t'];

    }

    /**
     * Has Cache
     *
     * @param $id
     *
     * @return bool
     */
    private function hasCache($id)
    {
        return ($this->cache !== null && $this->cache->exists($this->getCacheKey($id)));
    }

    /**
     * Set Cache
     *
     * @param $id
     * @param $text
     */
    private function setCache($id, $text, $expiry = null)
    {
        if ($this->cache !== null) {
            $this->cache->set($this->getCacheKey($id), $text);
            if (!empty($expiry)) {
                $this->cache->expire($this->getCacheKey($id), $expiry);
            }
        }
    }

    /**
     * Set Batch of Cache keys
     *
     * @param $messages
     * @param $slugTextIdMapReversed
     */
    private function setCacheBatch($messages, $slugTextIdMapReversed, $ttl = null)
    {
        if ($this->cache !== null) {
            $cacheData = [];
            foreach ($messages as $k => $v) {
                $cacheData[$this->getCacheKey($slugTextIdMapReversed[$k])] = $v;
            }
            $this->cache->mset($cacheData);

            if (!empty($ttl)) {
                foreach ($messages as $k => $v) {
                    $this->cache->expire($this->getCacheKey($slugTextIdMapReversed[$k]), $ttl);
                }
            }
        }
    }

    /**
     * Get Cache Key
     *
     * @param $key
     *
     * @return string
     */
    private function getCacheKey($key)
    {
        return $this->cachePrefix . ':' . $key;
    }

    /**
     * Get Cache
     *
     * @param $key
     *
     * @return string
     */
    private function getCache($key)
    {
        return $this->cache->get($this->getCacheKey($key));
    }

    /**
     * Get Multiple Cache Keys
     * @param $keys
     *
     * @return array
     */
    private function getCacheMulti($keys)
    {
        $cacheKeys = [];
        foreach ($keys as $key) {
            $cacheKeys[] = $this->getCacheKey($key);
        }
        return $this->cache->mget($cacheKeys);
    }
}