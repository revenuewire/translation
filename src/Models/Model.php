<?php
namespace RW\Models;
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
     * Get DynamoDB client
     * @param $table
     *
     */
    public static function init($table)
    {
        self::$marshaller = new Marshaler();

        $class = get_called_class();
        $class::$client = new DynamoDbClient($table);
        $class::$table = $table['name'];
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
        $object = new $class($class::$table);
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
        $class = get_called_class();
        $result = $class::$client->getItem(array(
            'TableName' => $class::$table,
            'Key' => array(
                'id' => array('S' => $id)
            ),
            'ConsistentRead' => true,
        ));

        return self::populateItemToObject($result->get('Item'));
    }

    /**
     * Save
     *
     * @return $this
     */
    public function save()
    {
        $class = get_called_class();
        if ($this->isNew) {
            $this->isNew = false;
            $class::$client->putItem(array(
                'TableName' => $class::$table,
                'Item' => self::$marshaller->marshalItem($this->data),
                'ConditionExpression' => 'attribute_not_exists(id)',
                'ReturnValues' => 'ALL_OLD'
            ));

            return $this;
        }

        if ($this->isModified()) {
            $expressionAttributeNames = [];
            $expressionAttributeValues = [];
            $updateExpressionHolder = [];
            foreach ($this->modifiedColumns as $field => $hasModified) {
                if ($hasModified === true) {
                    $expressionAttributeNames['#' . $field] = $field;
                    $expressionAttributeValues[':'.$field] = self::$marshaller->marshalValue($this->data[$field]);
                    $updateExpressionHolder[] = "#$field = :$field";

                    $this->modifiedColumns[$field] = false;
                }
            }
            $updateExpression = implode(', ', $updateExpressionHolder);

            $updateAttributes = [
                'TableName' => $class::$table,
                'Key' => array(
                    'id' => self::$marshaller->marshalValue($this->getId())
                ),
                'ExpressionAttributeNames' =>$expressionAttributeNames,
                'ExpressionAttributeValues' =>  $expressionAttributeValues,
                'ConditionExpression' => 'attribute_exists(id)',
                'UpdateExpression' => "set $updateExpression",
                'ReturnValues' => 'ALL_NEW'
            ];

            $class::$client->updateItem($updateAttributes);
        }

        return $this;
    }
}