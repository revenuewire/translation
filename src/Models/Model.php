<?php
namespace Models;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

/**
 * Class Model
 */
class Model
{
    protected $data;
    protected $isNew;
    protected $modifiedColumns;
    protected $isModified;

    /** @var $client DynamoDbClient */
    public static $client;

    /** @var $table string */
    public static $table;

    /** @var $marshaller Marshaler */
    public static $marshaller;

    /**
     * TranslationQueue constructor.
     */
    function __construct()
    {
        $this->isNew = true;
    }

    /**
     * Get DynamoDB client
     * @param $table
     *
     * @return DynamoDbClient
     */
    public static function init($table)
    {
        self::$client = new DynamoDbClient([
            "region" => $table['region'],
            "version" => $table['version'],
        ]);
        self::$marshaller = new Marshaler();
        self::$table = $table['name'];
    }

    /**
     * Populate Item into object
     *
     * @param $item
     *
     * @return Model
     */
    public static function populateItemToObject($item)
    {
        if (empty($item)) {
            return null;
        }

        $class = get_called_class();
        $object = new $class(self::$table);
        foreach (self::$marshaller->unmarshalItem($item) as $k => $v) {
            $object->data[$k] = $v;
        }
        $object->isNew = false;
        return $object;
    }

    /**
     * Get a translation queue item by ID
     *
     * @param $id
     *
     * @return Model
     */
    public static function getById($id)
    {
        $result = self::$client->getItem(array(
            'TableName' => self::$table,
            'Key' => array(
                'id' => array('S' => $id)
            ),
            'ConsistentRead' => true,
        ));

        return self::populateItemToObject($result->get('Item'));
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->data["id"];
    }


    /**
     * Get Property
     *
     * @param $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->data[$property];
        }
    }

    /**
     * Set Property
     *
     * @param $property
     * @param $value
     */
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->data[$property] = $value;
        }
    }

    /**
     * Get A dump of data
     */
    public function debug()
    {
        var_dump($this->data);
    }

    /**
     * Return true if the object has been modified.
     *
     * @return bool
     */
    public function isModified()
    {
        return !empty($this->modifiedColumns);
    }

    /**
     * Save
     *
     * @return $this
     */
    public function save()
    {

        if ($this->isNew) {
            $this->isNew = false;
            $this->dbClient->putItem(array(
                'TableName' => $this->table,
                'Item' => $this->marshaler->marshalItem($this->data),
                'ConditionExpression' => 'attribute_not_exists(id)',
                'ReturnValues' => 'ALL_OLD'
            ));

            return $this;
        }

        $expressionAttributeNames = [];
        $expressionAttributeValues = [];
        $updateExpressionHolder = [];
        foreach ($this->modifiedColumns as $field => $hasModified) {
            if ($hasModified === true) {
                $expressionAttributeNames['#' . $field] = $field;
                $expressionAttributeValues[':'.$field] = $this->marshaler->marshalValue($this->data[$field]);
                $updateExpressionHolder[] = "#$field = :$field";

                $this->modifiedColumns[$field] = false;
            }
        }
        $updateExpression = implode(', ', $updateExpressionHolder);

        $updateAttributes = [
            'TableName' => $this->table,
            'Key' => array(
                'id' => $this->marshaler->marshalValue($this->getId())
            ),
            'ExpressionAttributeNames' =>$expressionAttributeNames,
            'ExpressionAttributeValues' =>  $expressionAttributeValues,
            'ConditionExpression' => 'attribute_exists(id)',
            'UpdateExpression' => "set $updateExpression",
            'ReturnValues' => 'ALL_NEW'
        ];

        $this->dbClient->updateItem($updateAttributes);

        return $this;
    }
}