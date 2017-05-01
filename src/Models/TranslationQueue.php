<?php
namespace Models;

class TranslationQueue
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

}