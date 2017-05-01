<?php
namespace Models;

/**
 * Translation
 */
class Translation
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
    public function getText()
    {
        return $this->t;
    }

    /**
     * @param string $t
     */
    public function setText($t)
    {
        $this->t = $t;
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->l;
    }

    /**
     * @param string $l
     */
    public function setLang($l)
    {
        $this->l = $l;
    }
}