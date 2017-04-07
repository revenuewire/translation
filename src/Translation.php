<?php
namespace RW;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Predis\Client;

class Translation
{
    protected $app;
    protected $db;
    protected $marshaler;
    public $supportLanguages;
    public $maxIdLength;

    /** @var $cache Client  */
    public $cache = null;
    public $cachePrefix = null;

    function __construct($name, $supportLanguages = array('en'), $cache = null, $maxIdLength = 50)
    {
        $this->app = $name;

        /** @var $db DynamoDbClient */
        $this->db = new DynamoDbClient(array(
            "region" => "ca-central-1",
            'version' => '2012-08-10',
        ));
        $this->table = "translation_" . $this->app;

        /** @var marshaler Marshaler */
        $this->marshaler = new Marshaler();

        $this->maxIdLength = $maxIdLength;
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
    public function batchTranslate($messages = array(), $lang = "en", $namespace = "default")
    {
        if ($this->app == null) {
            return $messages;
        }

        if (!in_array($lang, $this->supportLanguages)) {
            return $messages;
        }

        $batchKeys = [];
        $slugTextIdMap = [];
        $slugTextIdMapReversed = [];
        foreach ($messages as $textId => $text) {
            $text = trim($text);
            if (empty($text)) {
                throw new \InvalidArgumentException("Must provide a default text.");
            }
            $slugTextId = $this->getId($text, $textId);
            if ($slugTextId != $textId) {
                throw new \InvalidArgumentException("In Batch mode, the text id must be slugified and less than {$this->maxIdLength} characters.");
            }
            $id = $this->getHash($namespace, $lang, $slugTextId);
            $batchKeys[] = ['id' => $this->marshaler->marshalValue($id)];
            $slugTextIdMap[$id] = $slugTextId;
            $slugTextIdMapReversed[$slugTextId] = $id;
        }

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
        $batchData = [];
        foreach ($missingMessages as $k => $v) {
            $batchData[] = ['PutRequest' => [
                "Item" => $this->marshaler->marshalItem([
                    'id' => $slugTextIdMapReversed[$k],
                    't' => $v,
                    'meta' => [
                        'app' => $this->app,
                        'lang' => $lang,
                        'namespace' => $namespace,
                        'textId' => $k,
                        'slug' => $k,
                    ],
                    's' => ($lang != 'en'),
                    'lang' => $lang,
                ])
            ]];
        }
        if (count($batchData) > 0) {
            $this->db->batchWriteItem([
                'RequestItems' => [
                    $this->table => $batchData
                ]
            ]);
        }

        $resultMessages = array_merge($messages, $resultMessages);
        $this->setCacheBatch($resultMessages, $slugTextIdMapReversed);
        return $resultMessages;
    }

    /**
     * Translate a single text
     *
     * @param $text
     * @param string $lang
     * @param null $textId
     * @param string $namespace
     *
     * @return string
     */
    public function translate($text, $lang = 'en', $textId = null, $namespace = "default")
    {
        if ($this->app == null || !in_array($lang, $this->supportLanguages)) {
            return $text;
        }

        $text = trim($text);
        if (empty($text)) {
            return $text;
        }

        $slugTextId = $this->getId($text, $textId);
        $id = $this->getHash($namespace, $lang, $slugTextId);

        if ($this->hasCache($id)) {
            return $this->getCache($id);
        }

        $result = $this->db->getItem(array(
            'TableName' => $this->table,
            'Key' => array(
                'id' => array('S' => $id)
            ),
            'ConsistentRead' => false,
            'ProjectionExpression' => 'id, t',
        ));

        if (empty($result['Item'])) {
            $data = [
                'id' => $id,
                't' => $text,
                'meta' => [
                    'app' => $this->app,
                    'lang' => $lang,
                    'namespace' => $namespace,
                    'textId' => $textId,
                    'slug' => $slugTextId
                ],
                's' => ($lang != 'en'), //need translate if not en
                'lang' => $lang,
            ];
            $this->db->putItem(array(
                'TableName' => $this->table,
                'Item' => $this->marshaler->marshalItem($data),
                'ReturnValues' => 'ALL_OLD'
            ));

            $this->setCache($id, $text);
            return $text;
        }

        $data = $this->marshaler->unmarshalItem($result['Item']);
        $this->setCache($id, $data['t']);
        return $data['t'];
    }

    /**
     * Get Hash
     *
     * @param $namespace
     * @param $lang
     * @param $id
     *
     * @return string
     */
    public function getHash($namespace, $lang, $id)
    {
        return hash('ripemd160', implode('|:|', array($namespace, $lang, $id)));
    }

    /**
     * Get ID
     *
     * @param $text
     * @param null $id
     *
     * @return mixed|string
     */
    public function getId($text, $id = null)
    {
        if (!empty($id)) {
            return \Utils::slugify($id);
        }

        return \Utils::slugify(strlen($text) > $this->maxIdLength ? substr($text, 0, $this->maxIdLength) . hash('crc32', substr($text, 11)) : $text);
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
    private function setCache($id, $text)
    {
        if ($this->cache !== null) {
            $this->cache->set($this->getCacheKey($id), $text);
        }
    }

    /**
     * Set Batch of Cache keys
     *
     * @param $messages
     * @param $slugTextIdMapReversed
     */
    private function setCacheBatch($messages, $slugTextIdMapReversed)
    {
        if ($this->cache !== null) {
            $cacheData = [];
            foreach ($messages as $k => $v) {
                $cacheData[$this->getCacheKey($slugTextIdMapReversed[$k])] = $v;
            }
            $this->cache->mset($cacheData);
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
        array_walk($keys, function ($item1, &$key, $prefix){
            $key = $prefix.$key;
        }, $this->cachePrefix);
        return $this->cache->mget($keys);
    }
}