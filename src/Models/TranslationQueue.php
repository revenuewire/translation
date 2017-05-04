<?php
namespace Models;

use Aws\DynamoDb\DynamoDbClient;

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
        'GlobalSecondaryIndexes' => [
            [
                'IndexName' => 'project-index',
                'KeySchema' => [
                    [
                        'AttributeName' => 'projectId', // REQUIRED
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
     * The translated text
     * @var $targetResult string
     */
    public $targetResult;

    /**
     * The project ID once the text is being batched
     * @var $projectId string
     */
    public $projectId;

    /**
     * The status of the queue, either PENDING or COMPLETED
     * @var $status string
     */
    public $status;
    const STATUS_PENDING = "PENDING";
    const STATUS_READY = "READY";
    const STATUS_COMPLETED = "COMPLETED";
    const STATUS_ERROR = "ERROR";

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
     * @param $targetProvider
     *
     * @return string
     */
    public static function idFactory($sourceId, $targetLanguage, $targetProvider)
    {
        return $sourceId . '_' . $targetLanguage . '_' . $targetProvider;
    }

    /**
     * ID Exploder
     *
     * @param $id
     *
     * @return array
     */
    public static function idExplode($id)
    {
        return explode('_', $id);
    }

    /**
     * Get Queue Items By ProjectID
     *
     * @param string $projectId
     *
     * @return mixed
     */
    public static function getQueueItemsByProjectId($config, $projectId)
    {
        $dbClient = new DynamoDbClient([
            "region" => $config['region'],
            "version" => $config['version'],
        ]);

        $queryAttributes = array(
            'TableName' => $config['name'],
            'IndexName' => 'project-index',
            'ExpressionAttributeNames' => array(
                '#projectId' => 'projectId'
            ),
            'ExpressionAttributeValues' => array(
                ':projectId' => array('S' => $projectId),
            ),
            'KeyConditionExpression' => '#projectId = :projectId'
        );
       
        $items = [];
        $result = $dbClient->query($queryAttributes);
        foreach ($result->get('Items') as $item) {
            $items[] = TranslationQueue::populateItemToObject($config, $item);
        }

        return $items;
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
        if ($this->data["id"] != $id) {
            $this->data["id"] = $id;
            $this->modifiedColumns["id"] = true;
        }
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
        if ($this->data["targetId"] != $targetId) {
            $this->data["targetId"] = $targetId;
            $this->modifiedColumns["targetId"] = true;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getTargetResult()
    {
        return $this->data["targetResult"];
    }

    /**
     * @param string $targetResult
     *
     * @return TranslationQueue
     */
    public function setTargetResult($targetResult)
    {
        if ($this->data["targetResult"] != $targetResult) {
            $this->data["targetResult"] = $targetResult;
            $this->modifiedColumns["targetResult"] = true;
        }
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
        if ($this->data["projectId"] != $projectId) {
            $this->data["projectId"] = $projectId;
            $this->modifiedColumns["projectId"] = true;
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
     * @return TranslationQueue
     */
    public function setStatus($status)
    {
        if ($this->data["status"] != $status) {
            $this->data["status"] = $status;
            $this->modifiedColumns["status"] = true;
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
     * @return TranslationQueue
     */
    public function setCreated($created)
    {
        if ($this->data["created"] != $created) {
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
     * @return TranslationQueue
     */
    public function setModified($modified)
    {
        if ($this->data["modified"] != $modified) {
            $this->data["modified"] = $modified;
            $this->modifiedColumns["modified"] = true;
        }
        return $this;
    }

}