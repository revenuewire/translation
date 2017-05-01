<?php
namespace Models;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

class TranslationQueue extends Model
{
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
        'ProvisionedThroughput' => [
            'ReadCapacityUnits' => 1,
            'WriteCapacityUnits' => 1,
        ],
    ];

    /**
     * Source ID + Language Code
     * @var $id string
     */
    public $id;

    /**
     * The target ID once the text being translated
     * @var $targetId string
     */
    public $targetId;

    /**
     * The project ID once the text is being batched
     * @var $projectId string
     */
    public $projectId;

    /**
     * The translation service provider, currently only support OHT(OneHourTranslation) and GCT(GoogleCloudTranslation)
     * @var $provider string
     */
    public $provider;
    const PROVIDER_ONE_HOUR_TRANSLATION = "OHT";
    const PROVIDER_GOOGLE_CLOUD_TRANSLATION = "GCT";

    /**
     * The status of the queue, either PENDING or COMPLETED
     * @var $status string
     */
    public $status;
    const STATUS_PENDING = "PENDING";
    const STATUS_COMPLETED = "COMPLETED";

    /**
     * The date of the queue created in unix timestamp
     * @var $created integer
     */
    public $created;

    /**
     * The date of the queue modified in unix timestamp
     * @var $modified integer
     */
    public $modified;

    /**
     * ID Factory for Queue Item
     *
     * @param $sourceId
     * @param $targetLanguage
     *
     * @return string
     */
    public static function idFactory($sourceId, $targetLanguage)
    {
        return $sourceId . '_' . $targetLanguage;
    }

    /**
     * Get a translation queue item by ID
     *
     * @param $table
     * @param $id
     *
     * @return TranslationQueue
     */
    public static function getById($table, $id)
    {
        $dbClient = new DynamoDbClient([
            "region" => $table['region'],
            "version" => $table['version'],
        ]);
        $name =  $table['name'];

        $result = $dbClient->getItem(array(
            'TableName' => $name,
            'Key' => array(
                'id' => array('S' => $id)
            ),
            'ConsistentRead' => true,
        ));

        return self::populateItemToObject($table, $result->get('Item'));
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
     * @return TranslationQueue
     */
    public function setId($id)
    {
        $this->data["id"] = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTargetId()
    {
        return $this->data["targetId"];
    }

    /**
     * @param string $targetId
     *
     * @return TranslationQueue
     */
    public function setTargetId($targetId)
    {
        $this->data["targetId"] = $targetId;
        return $this;
    }

    /**
     * @return string
     */
    public function getProjectId()
    {
        return $this->data["projectId"];
    }

    /**
     * @param string $projectId
     *
     * @return TranslationQueue
     */
    public function setProjectId($projectId)
    {
        $this->data["projectId"] = $projectId;
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
     * @return TranslationQueue
     */
    public function setProvider($provider)
    {
        $this->data["provider"] = $provider;
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
     * @return TranslationQueue
     */
    public function setStatus($status)
    {
        $this->data["status"] = $status;
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
     * @return TranslationQueue
     */
    public function setCreated($created)
    {
        $this->data["created"] = $created;
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
     * @return TranslationQueue
     */
    public function setModified($modified)
    {
        $this->data["modified"] = $modified;
        return $this;
    }


}