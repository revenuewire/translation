<?php
namespace Models;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

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
     * Get Unique ID
     *
     * @return string
     */
    public static function idFactory()
    {
        return uniqid();
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
     * @return TranslationProject
     */
    public function setId($id)
    {
        $this->data["id"] = $id;
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
        $this->data["targetLanguage"] = $targetLanguage;
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
     * @return TranslationProject
     */
    public function setModified($modified)
    {
        $this->data["modified"] = $modified;
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
        $this->data["status"] = $status;
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
        $this->data["projectData"] = $projectData;
        return $this;
    }
}