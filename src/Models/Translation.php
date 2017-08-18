<?php
namespace RW\Models;
use Aws\DynamoDb\DynamoDbClient;
use RW\Services\Languages;
use RW\Utils;

/**
 * Translation
 */
class Translation extends Model
{
    const MAX_KEY_LENGTH = 50;

    /**
     * DynamoDB Schema Definition
     */
    public static $schema = [
        "AttributeDefinitions" => [
            [
                'AttributeName' => 'id',
                'AttributeType' => 'S',
            ],
            [
                'AttributeName' => 'l',
                'AttributeType' => 'S',
            ]
        ],
        'KeySchema' => [
            [
                'AttributeName' => 'id',
                'KeyType' => 'HASH',
            ]
        ],

        'GlobalSecondaryIndexes' => [
            [
                'IndexName' => 'l-idx',
                'KeySchema' => [
                    [
                        'AttributeName' => 'l', // REQUIRED
                        'KeyType' => 'HASH', // REQUIRED
                    ],
                ],
                'Projection' => [
                    'ProjectionType' => 'ALL',
                ],
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits' => 1,
                    'WriteCapacityUnits' => 1,
                ],
            ],
        ],

        'ProvisionedThroughput' => [
            'ReadCapacityUnits' => 5,
            'WriteCapacityUnits' => 5,
        ],
    ];

    /**
     * Unique ID
     * @var $id string
     */
    public $id;

    /**
     * Language Text
     * @var $t string
     */
    public $t;

    /**
     * Language Code
     *
     * @var $l string
     */
    public $l;

    /**
     * Namespace
     *
     * @var $n string
     */
    public $n;

    /** @var $client DynamoDbClient */
    public static $client;

    /** @var $table string */
    public static $table;
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
        $id = Utils::slugify(strlen($text) > self::MAX_KEY_LENGTH
            ? substr($text, 0, self::MAX_KEY_LENGTH) . hash('crc32', substr($text, self::MAX_KEY_LENGTH+1))
            : $text);

        return hash('ripemd160', implode('|:|', array($lang, $id, $namespace)));
    }

    /**
     * Get ALl Texts By Language
     *
     * @param string $lang
     * @param null $limit
     *
     * @return array
     */
    public static function getAllTextsByLanguage($lang = Languages::DEFAULT_LANGUAGE_CODE, $limit = null)
    {
        $lastEvaluatedKey = null;
        $items = [];

        do {
            $queryAttributes = array(
                'TableName' => self::$table,
                'IndexName' => 'l-idx',
                'ExpressionAttributeNames' => array(
                    '#l' => 'l'
                ),
                'ExpressionAttributeValues' => array(
                    ':l' => array('S' => $lang),
                ),
                'KeyConditionExpression' => '#l = :l'
            );
            if ($lastEvaluatedKey != null) {
                $queryAttributes['ExclusiveStartKey'] = $lastEvaluatedKey;
            }
            if ($limit > 0) {
                $queryAttributes['Limit'] = $limit;
            }

            $result = self::$client->query($queryAttributes);
            foreach ($result->get('Items') as $item) {
                $items[] = Translation::populateItemToObject($item);
            }
        } while ($lastEvaluatedKey !== null);

        return $items;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->data["id"];
    }

    /**
     * @param string $id
     *
     * @return Translation
     */
    public function setId($id)
    {
        if (empty($this->data["id"]) || $this->data["id"] != $id) {
            $this->data["id"] = $id;
            $this->modifiedColumns["id"] = true;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->data["n"];
    }

    /**
     * @param string $namespace
     *
     * @return Translation
     */
    public function setNamespace($namespace)
    {
        if (empty($this->data["n"]) || $this->data["n"] != $namespace) {
            $this->data["n"] = $namespace;
            $this->modifiedColumns["n"] = true;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->data["t"];
    }

    /**
     * @param string $t
     *
     * @return Translation
     */
    public function setText($t)
    {
        if (empty($this->data["t"]) || $this->data["t"] != $t) {
            $this->data["t"] = $t;
            $this->modifiedColumns["t"] = true;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->data["l"];
    }

    /**
     * @param string $l
     *
     * @return Translation
     */
    public function setLang($l)
    {
        if (empty($this->data["l"]) || $this->data["l"] != $l) {
            $this->data["l"] = $l;
            $this->modifiedColumns["l"] = true;
        }
        return $this;
    }
}