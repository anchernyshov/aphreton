<?php

namespace Aphreton;

/**
 * Base class for all model classes
 * 
 * To create custom model you need to extend Model class and set $connection and $table_name
 * properties in constructor of the derived class
 * 
 * Example:
 * 
 * class TestModel extends \Aphreton\Model {
 *     public function __construct() {
 *         parent::__construct();
 *         $this->connection = \Aphreton\DatabasePool::getInstance()->getDatabase('DATABASE_NAME');
 *         $this->source_name = 'TABLE_NAME';
 *     }
 *     ...
 * }
 */
class Model {

    /**
     * @var \Aphreton\DatabaseConnection
     */
    protected $connection;
    /**
     * Name of database entity where model data is stored
     * @var string
     */
    protected string $source_name;

    public function __construct() { }

    /**
     * Queries database and returns constructed derived class instance with found data
     * 
     * Example:
     * TestModel $x = TestModel::get(['test' => '123']);
     * 
     * @param array $params
     * 
     * @return null|object
     */
    public static function get($params = []) {
        if (empty($params)) {
            //Empty database search parameters, restrict for now
            //TODO: handle multiple records in search result
            throw new \Exception('API error');
        }
        
        // get instance of a caller class: DerivedClass::get(...) => new DerivedClass()
        $class = new \ReflectionClass(get_called_class());
        $entity = $class->newInstance();
        
        $e_source_name = $entity->getSourceName();
        $e_connection = $entity->getConnection();

        $records = $e_connection->find($e_source_name, $params);
        if (!empty($records)) {
            $record = $records[0];
            foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
                if (isset($record[$prop->getName()])) {
                    $prop->setValue($entity, $record[$prop->getName()]);
                }
            }
            return $entity;
        } else {
            return null;
        }
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
