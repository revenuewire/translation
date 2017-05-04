<?php
namespace RW;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Predis\Client;

class Translation
{
    const DefaultLanguage = 'en';

    /**
     * The max length you want to put for key. Note: If the key length is very small, you might run into collision.
     * If it is too big, you wasted space.
     */
    const keyLength = 50;

    public $dynamoSettings = array();
    protected $db;
    protected $marshaler;
    public $supportLanguages;

    /** @var $cache Client  */
    public $cache = null;
    public $cachePrefix = null;

    /**
     * Translation constructor.
     *
     * @param $dynamoSettings must consistent with region, version and table name.
     * @param array $supportLanguages
     * @param null $cache cache is recommended for performance. Only support Redis for now
     */
    function __construct($dynamoSettings, $supportLanguages = array('en'), $cache = null)
    {
        if (empty($dynamoSettings)
                || empty($dynamoSettings['table'])
                || empty($dynamoSettings['region'])
                || empty($dynamoSettings['version'])) {
            throw new \InvalidArgumentException("DyanmoDB is required.");
        }

        /** @var $db DynamoDbClient */
        $this->db = new DynamoDbClient(array(
            "region" => $dynamoSettings['region'],
            'version' => $dynamoSettings['version'],
        ));
        $this->table = $dynamoSettings['table'];

        /** @var marshaler Marshaler */
        $this->marshaler = new Marshaler();

        $this->supportLanguages = $supportLanguages;

        if ($cache !== null) {
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
    }

    /**
     * Batch Translate
     *
     * @param array $messages
     * @param string $lang
     * @param string $namespace
     *
     * @return array
     */
    public function batchTranslate($messages = array(), $lang = "en", $namespace = "t_")
    {
        if (!in_array($lang, $this->supportLanguages)) {
            return $messages;
        }

        if (empty($messages)) {
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
            $id = \RW\Models\Translation::idFactory($namespace, $lang, $text);
            $batchKeys[] = ['id' => $this->marshaler->marshalValue($id)];
            $slugTextIdMap[$id] = $textId;
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

        $results = $this->db->batchGetItem([
            'RequestItems' => [
                $this->table => [
                    "Keys" => $batchKeys,
                    'ConsistentRead' => false,
                    'ProjectionExpression' => 'id, t',
                ]
            ]
        ]);
        $resultMessages = [];
        foreach ($results['Responses'][$this->table] as $response) {
            $id = $response['id']['S'];
            $text = $response['t']['S'];
            $resultMessages[$slugTextIdMap[$id]] = $text;
        }

        $missingMessages = array_diff($messages, $resultMessages);
        $resultMessages = array_merge($messages, $resultMessages);
        if ($lang == self::DefaultLanguage) {
            $batchData = [];
            foreach ($missingMessages as $k => $v) {
                $batchData[] = ['PutRequest' => [
                    "Item" => $this->marshaler->marshalItem([
                        'id' => $slugTextIdMapReversed[$k],
                        't' => $v,
                        'l' => $lang,
                        'n' => $namespace,
                    ])
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
     * @param string $namespace
     *
     * @return string
     */
    public function translate($text, $lang = self::DefaultLanguage, $namespace = "t_")
    {
        /**
         * If language is not in supported languages, just return original text back
         */
        if (!in_array($lang, $this->supportLanguages)) {
            return $text;
        }

        /**
         * If the text is empty, return it with empty string.
         */
        $text = trim($text);
        if (empty($text)) {
            return $text;
        }

        $id = \RW\Models\Translation::idFactory($namespace, $lang, $text);

        if ($this->hasCache($id)) {
            return $this->getCache($id);
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
            if ($lang == self::DefaultLanguage) {
                $data = [
                    'id' => $id,
                    't' => $text,
                    'l' => $lang,
                    'n' => $namespace,
                ];
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
        return $this->cachePrefix . $key;
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