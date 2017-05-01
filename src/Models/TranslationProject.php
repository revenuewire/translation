<?php
namespace Models;

/**
 * Translation Project
 */
class TranslationProject
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
     * The project size. Mostly due to a limit in the service provider side. For example, OHT only allow a maximum
     * of 30 translation requests per projects
     *
     * @var $size integer
     */
    public $size;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getTargetLanguage()
    {
        return $this->targetLanguage;
    }

    /**
     * @param string $targetLanguage
     */
    public function setTargetLanguage($targetLanguage)
    {
        $this->targetLanguage = $targetLanguage;
    }

    /**
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param int $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return int
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @param int $modified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getProjectData()
    {
        return $this->projectData;
    }

    /**
     * @param mixed $projectData
     */
    public function setProjectData($projectData)
    {
        $this->projectData = $projectData;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }
}