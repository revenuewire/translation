<?php
namespace Models;
use Aws\DynamoDb\DynamoDbClient;

/**
 * Translation
 */
class Translation extends Model
{
    const DEFAULT_LANGUAGE_CODE = "en";

    /**
     * DynamoDB Schema Definition
     */
    public static $schema = [
        "AttributeDefinitions" => [
            [
                'AttributeName' => 'id',
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
                'IndexName' => 'lang-idx',
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
     * Get ALl Texts By Language
     *
     * @param $config
     * @param string $lang
     * @param null $limit
     *
     * @return array
     */
    public static function getAllTextsByLanguage($config, $lang = self::DEFAULT_LANGUAGE_CODE, $limit = null)
    {
        $lastEvaluatedKey = null;
        $items = [];

        $dbClient = new DynamoDbClient([
            "region" => $config['region'],
            "version" => $config['version'],
        ]);

        do {
            $queryAttributes = array(
                'TableName' => $config['name'],
                'IndexName' => 'l-index',
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

            $result = $dbClient->query($queryAttributes);
            foreach ($result->get('Items') as $item) {
                $items[] = Translation::populateItemToObject($config, $item);
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
        if ($this->data["id"] != $id) {
            $this->data["id"] = $id;
            $this->modifiedColumns["id"] = true;
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
        if ($this->data["t"] != $t) {
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
        if ($this->data["l"] != $l) {
            $this->data["l"] = $l;
            $this->modifiedColumns["l"] = true;
        }
        return $this;
    }

}