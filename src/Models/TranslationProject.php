<?php
namespace Models;

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
     * The translation service provider, currently only support OHT(OneHourTranslation) and GCT(GoogleCloudTranslation)
     * @var $provider string
     */
    public $provider;
    const PROVIDER_ONE_HOUR_TRANSLATION = "OHT";
    const PROVIDER_GOOGLE_CLOUD_TRANSLATION = "GCT";

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
        if ($this->data["id"] != $id) {
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
        if ($this->data["targetLanguage"] != $targetLanguage) {
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
     * @return TranslationProject
     */
    public function setModified($modified)
    {
        if ($this->data["modified"] != $modified) {
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
        if ($this->data["status"] != $status) {
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
        if ($this->data["projectData"] != $projectData) {
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
        if ($this->data["provider"] != $provider) {
            $this->data["provider"] = $provider;
            $this->modifiedColumns["provider"] = true;
        }
        return $this;
    }
}