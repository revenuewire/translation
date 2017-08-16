<?php
namespace RW\Models;
use Aws\DynamoDb\DynamoDbClient;

/**
 * Translation Project
 */
class TranslationProject extends Model
{
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
                'AttributeName' => 'status',
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
                'IndexName' => 'status-index',
                'KeySchema' => [
                    [
                        'AttributeName' => 'status', // REQUIRED
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
            'ReadCapacityUnits' => 1,
            'WriteCapacityUnits' => 1,
        ],
    ];

    /**
     * Project ID
     * @var $id string
     */
    public $id;

    /**
     * Target Language Code
     *
     * @var $targetLanguage string
     */
    public $targetLanguage;

    /**
     * The date of the project created in unix timestamp
     * @var $created integer
     */
    public $created;

    /**
     * The date of the project modified in unix timestamp
     * @var $created integer
     */
    public $modified;

    /**
     * The status of the project, either PENDING, IN_PROGRESS, or COMPLETED
     * @var $status string
     */
    public $status;
    const STATUS_PENDING = "PENDING";
    const STATUS_IN_PROGRESS = "IN_PROGRESS";
    const STATUS_COMPLETED = "COMPLETED";

    /**
     * The project data specific to the choice of a provider
     * @var $projectData mixed
     */
    public $projectData;

    /**
     * The translation service provider, currently only support OHT(OneHourTranslation) and GCT(GoogleCloudTranslation)
     * @var $provider string
     */
    public $provider;
    const PROVIDER_ONE_HOUR_TRANSLATION = "OHT";
    const PROVIDER_GOOGLE_CLOUD_TRANSLATION = "GCT";

    /** @var $client DynamoDbClient */
    public static $client;

    /** @var $table string */
    public static $table;

    /**
     * Get Unique ID
     *
     * @return string
     */
    public static function idFactory()
    {
        return uniqid();
    }

    /**
     * Get Projects by status
     *
     * @param $status mixed
     *
     * @return mixed
     */
    public static function getProjectsByStatus($status)
    {
        if (is_scalar($status)) {
            $status = array($status);
        }

        $items = [];

        foreach ($status as $s) {
            $queryAttributes = array(
                'TableName' => self::$table,
                'IndexName' => 'status-index',
                'ExpressionAttributeNames' => array(
                    '#status' => 'status'
                ),
                'ExpressionAttributeValues' => array(
                    ':status' => array('S' => $s),
                ),
                'KeyConditionExpression' => '#status = :status'
            );

            $result = self::$client->query($queryAttributes);
            foreach ($result->get('Items') as $item) {
                $itemData = TranslationProject::populateItemToObject($item);
                $items[] = $itemData;
            }
        }

        return $items;
    }

    /**
     * Get Projects By Ids
     *
     * @param $ids
     *
     * @return array
     */
    public static function getProjectsByIds($ids)
    {
        $batchKeys = [];
        foreach ($ids as $id) {
            $batchKeys[] = ['id' => ["S" => $id]];
        }

        $result = self::$client->batchGetItem([
            'RequestItems' => [
                self::$table => [
                    "Keys" => $batchKeys,
                    'ConsistentRead' => false,
                ]
            ]
        ]);

        $items = [];
        foreach ($result['Responses'][self::$table] as $item) {
            $itemData = TranslationProject::populateItemToObject($item);
            $items[] = $itemData;
        }

        return $items;
    }

    /**
     * @param string $id
     *
     * @return TranslationProject
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
    public function getTargetLanguage()
    {
        return $this->data["targetLanguage"];
    }

    /**
     * @param string $targetLanguage
     *
     * @return TranslationProject
     */
    public function setTargetLanguage($targetLanguage)
    {
        if (empty($this->data["targetLanguage"]) || $this->data["targetLanguage"] != $targetLanguage) {
            $this->data["targetLanguage"] = $targetLanguage;
            $this->modifiedColumns["targetLanguage"] = true;
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getCreated()
    {
        return $this->data["created"];
    }

    /**
     * @param int $created
     *
     * @return TranslationProject
     */
    public function setCreated($created)
    {
        if (empty($this->data["created"]) || $this->data["created"] != $created) {
            $this->data["created"] = $created;
            $this->modifiedColumns["created"] = true;
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getModified()
    {
        return $this->data["modified"];
    }

    /**
     * @param int $modified
     *
     * @return TranslationProject
     */
    public function setModified($modified)
    {
        if (empty($this->data["modified"]) || $this->data["modified"] != $modified) {
            $this->data["modified"] = $modified;
            $this->modifiedColumns["modified"] = true;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->data["status"];
    }

    /**
     * @param string $status
     *
     * @return TranslationProject
     */
    public function setStatus($status)
    {
        if (empty($this->data["status"]) || $this->data["status"] != $status) {
            $this->data["status"] = $status;
            $this->modifiedColumns["status"] = true;
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProjectData()
    {
        return $this->data["projectData"];
    }

    /**
     * @param mixed $projectData
     *
     * @return TranslationProject
     */
    public function setProjectData($projectData)
    {
        if (empty($this->data["projectData"]) || $this->data["projectData"] != $projectData) {
            $this->data["projectData"] = $projectData;
            $this->modifiedColumns["projectData"] = true;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->data["provider"];
    }

    /**
     * @param string $provider
     *
     * @return TranslationProject
     */
    public function setProvider($provider)
    {
        if (empty($this->data["provider"]) || $this->data["provider"] != $provider) {
            $this->data["provider"] = $provider;
            $this->modifiedColumns["provider"] = true;
        }
        return $this;
    }
}