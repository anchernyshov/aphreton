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
 *         $this->table_name = 'TABLE_NAME';
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
     * Name of database table where model data is stored
     * @var string
     */
    protected string $table_name;

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
        
        $e_table_name = $entity->getTableName();
        $e_connection = $entity->getConnection();

        $sql = "SELECT * FROM {$e_table_name}";
        $conditions = [];
        foreach ($params as $key => $value) {
            $conditions[] = "{$key} = :{$key}";
        }
        $sql .= ' WHERE ' . implode(' AND ', $conditions);

        $search_result = $e_connection->query($sql, $params);
        if (!empty($search_result)) {
            //TODO: handle multiple records in search result
            $source = $search_result[0];
            foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
                if (isset($source[$prop->getName()])) {
                    $prop->setValue($entity, $source[$prop->getName()]);
                }
            }
            return $entity;
        }
        return null;
    }

    public function getTableName() {
        return $this->table_name;
    }

    public function getConnection() {
        return $this->connection;
    }
}
