<?php
namespace Models;

/**
 * Class Model
 */
class Model
{
    protected $dbClient;
    protected $marshaler;
    protected $table;
    protected $data;
    protected $isNew;
    protected $modifiedColumns;
    protected $isModified;

    /**
     * TranslationQueue constructor.
     *
     * @param $table
     */
    function __construct($table)
    {
        $this->dbClient = new \Aws\DynamoDb\DynamoDbClient([
            "region" => $table['region'],
            "version" => $table['version'],
        ]);
        $this->table =  $table['name'];
        $this->marshaler = new \Aws\DynamoDb\Marshaler();
        $this->isNew = true;
    }

    /**
     * Populate Item into object
     *
     * @param $table
     * @param $item
     *
     * @return Model
     */
    public static function populateItemToObject($table, $item)
    {
        if (empty($item)) {
            return null;
        }

        $marshaller = new \Aws\DynamoDb\Marshaler();
        $class = get_called_class();
        $object = new $class($table);
        foreach ($marshaller->unmarshalItem($item) as $k => $v) {
            $object->data[$k] = $v;
        }
        $object->isNew = false;
        return $object;
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
     * @return \Aws\Result
     */
    public function save()
    {
        if ($this->isNew) {
            $this->isNew = false;
            return $this->dbClient->putItem(array(
                'TableName' => $this->table,
                'Item' => $this->marshaler->marshalItem($this->data),
                'ConditionExpression' => 'attribute_not_exists(id)',
                'ReturnValues' => 'ALL_OLD'
            ));
        }
    }
}