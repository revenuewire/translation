<?php
namespace RW\Models;

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
            ],
            [
                'AttributeName' => 'projectId',
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
     * Namespace
     *
     * @var $namespace string
     */
    public $namespace;

    /** @var $client DynamoDbClient */
    public static $client;

    /** @var $table string */
    public static $table;

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
    public static function getQueueItemsByProjectId($projectId)
    {
        $queryAttributes = array(
            'TableName' => self::$table,
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
        $result = self::$client->query($queryAttributes);
        foreach ($result->get('Items') as $item) {
            $items[] = TranslationQueue::populateItemToObject($item);
        }

        return $items;
    }

    /**
     * Get QueueItems By Status
     *
     * @param $status
     *
     * @return array
     */
    public static function getQueueItemsByStatus($status)
    {
        $queryAttributes = array(
            'TableName' => self::$table,
            'IndexName' => 'status-index',
            'ExpressionAttributeNames' => array(
                '#status' => 'status'
            ),
            'ExpressionAttributeValues' => array(
                ':status' => array('S' => $status),
            ),
            'KeyConditionExpression' => '#status = :status'
        );

        $items = [];
        $result = self::$client->query($queryAttributes);
        foreach ($result->get('Items') as $item) {
            $items[] = TranslationQueue::populateItemToObject($item);
        }

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
        if (empty($this->data["targetId"]) || $this->data["targetId"] != $targetId) {
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
        if (empty($this->data["targetResult"]) || $this->data["targetResult"] != $targetResult) {
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
        if (empty($this->data["projectId"]) || $this->data["projectId"] != $projectId) {
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
        if (empty($this->data["status"]) || $this->data["status"] != $status) {
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
     * @return TranslationQueue
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
     * @return mixed
     */
    public function getNamespace()
    {
        return $this->data["namespace"];
    }

    /**
     * @param mixed $namespace
     *
     * @return TranslationQueue
     */
    public function setNamespace($namespace)
    {
        if (empty($this->data["namespace"]) || $this->data["namespace"] != $namespace) {
            $this->data["namespace"] = $namespace;
            $this->modifiedColumns["namespace"] = true;
        }
        return $this;
    }

}