<?php

namespace Aphreton;

/**
 * Base class for all model classes
 * 
 * To create custom model you need to extend Model class and set $connection and $source_name
 * properties in constructor of the derived class
 * 
 * Example:
 * 
 * class TestModel extends \Aphreton\Model {
 *     public function __construct() {
 *         parent::__construct();
 *         $this->connection = \Aphreton\DatabasePool::getInstance()->getDatabase('DATABASE_NAME');
 *         $this->source_name = 'TABLE_NAME'; //or DATABASE_NAME.COLLECTION.NAME for mongoDB
 *     }
 *     ...
 * }
 */
abstract class Model {

    /**
     * @var \Aphreton\DatabaseConnection
     */
    protected $connection;
    /**
     * Name of database entity where model data is stored
     * @var string
     */
    protected string $source_name;
    /**
     * Unique record identifier, must be present in every model data source
     * @var int|\MongoDB\BSON\ObjectId
     */
    protected $_id = null;

    public function __construct() { }

    /**
     * Queries database and returns array of constructed derived class instances with found data
     * 
     * Example:
     * $x = TestModel::get(['test' => '123']);
     * echo $x[0]->getId();
     * 
     * @param array $params
     * 
     * @throws \Aphreton\APIException when $params == null
     * 
     * @return null|array
     */
    public static function get($params, $order_by = null, $limit = null, $offset = null) {
        if (empty($params)) {
            //Empty database search parameters, restrict for now
            throw new \Aphreton\APIException(
                'Attempt to perform model search with empty parameters',
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR
            );
        }
        
        //Get instance of a caller class: DerivedClass::get(...) => new DerivedClass()
        $class = new \ReflectionClass(get_called_class());
        $entity = $class->newInstance();
        $e_source_name = $entity->getSourceName();
        $e_connection = $entity->getConnection();
        unset($entity);

        $records = $e_connection->find($e_source_name, $params, $order_by, $limit, $offset);
        $result = [];
        if (!empty($records)) {
            $total = count($records);
            for ($i = 0; $i < $total; $i++) {
                $record = $records[$i];
                $entity = $class->newInstance();
                //Manually set $_id property of base class
                $entity->_id = $record['_id'];
                foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
                    if (isset($record[$prop->getName()])) {
                        $prop->setValue($entity, $record[$prop->getName()]);
                    }
                }
                $result[] = $entity;
            }
            return $result;
        } else {
            return null;
        }
    }

    /**
     * Alias for Model::get method with $order_by = null, $limit = 1 and $offset = null
     * 
     * @param array $params
     * 
     * @return null|object
     */
    public static function getOne($params) {
        $data = self::get($params, null, 1, null);
        if (!$data) {
            return null;
        }
        return $data[0];
    }

    /**
     * Saves current object to the database
     * 
     * @return void
     */
    public function save() {
        $reflection = new \ReflectionObject($this);
        //Does not include protected $_id property!
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $data = [];
        foreach ($properties as $prop) {
            if ($prop->getValue($this) !== null) {
                $data[$prop->getName()] = $prop->getValue($this);
            }
        }
        if (isset($this->_id)) {
             //If $_id field is set, database record exists => update
            $this->connection->update(['_id' => $this->_id], $data, $this->source_name);
        } else {
            //Inserting data and updating id field
            //TODO: Unique constraint check
            $this->_id = $this->connection->insert($data, $this->source_name);
        }
    }

    /**
     * Deletes current object from the database
     * 
     * @return void
     */
    public function delete() {
        if (!$this->connection->delete(['_id' => $this->_id], $this->source_name)) {
            //Deletion error
            throw new \Aphreton\APIException(
                'Model deletion error',
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR
            );
        }
    }

    /**
     * Converts all public class properties and _id property to associative array
     * 
     * @return array
     */
    public function toArray() {
        $result = [];
        $result['_id'] = $this->_id;
        $reflection = new \ReflectionObject($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $prop) {
            $result[$prop->getName()] = $prop->getValue($this);
        }
        return $result;
    }

    /**
     * Getter for $this->_id
     * 
     * @return int|\MongoDB\BSON\ObjectId
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * Getter for $this->source_name
     * 
     * @return string
     */
    public function getSourceName() {
        return $this->source_name;
    }

    /**
     * Getter for $this->connection
     * 
     * @return \Aphreton\DatabaseConnection
     */
    public function getConnection() {
        return $this->connection;
    }
}
