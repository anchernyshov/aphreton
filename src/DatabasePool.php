<?php

namespace Aphreton;

/**
 * Singleton class for managing multiple database connections
 * 
 * Usage example:
 * \Aphreton\DatabasePool::getInstance()->getDatabase('test')->query(...);
 */
class DatabasePool {

    /**
     * @var self
     */
    private static $instance = null;
    /**
     * Array containing \Aphreton\DatabaseConnection objects for each database name
     * @var array
     */
    private $databases = array();
    /**
     * Allowed database types defined by DSN prefixes
     * @var array
     */
    public const ALLOWED_DATABASE_TYPES = [
        'sqlite',
        'mongodb'
    ];

    /**
     * Gets the instance via lazy initialization 
     * 
     * @return self
     */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new DatabasePool();
        }
        return self::$instance;
    }

    /**
     * Adds new database to pool
     * 
     * @param string $name Database name
     * @param string $dsn Database data source name
     * @param string $user Database user
     * @param string $password Database password
     * 
     * @throws Exception if database type is not in self::ALLOWED_DATABASE_TYPES
     * 
     * @return void
     */
    public function addDatabase(string $name, string $dsn, string $user, string $password) {
        $type = explode(':', $dsn)[0];
        if (in_array($type, self::ALLOWED_DATABASE_TYPES)) {
            if ($type === 'sqlite') {
                $this->databases[$name] = new \Aphreton\PDOConnection($dsn, $user, $password);
            } else if ($type === 'mongodb') {
                $this->databases[$name] = new \Aphreton\MongoDBConnection($dsn, $user, $password);
            }
        } else {
            throw new \Exception("Database type $type is not allowed");
        }
    }

    /**
     * Retrieves database with given name from the pool
     * 
     * @param string $name Database name
     * 
     * @throws Exception if database with given name not exists
     * 
     * @return \Aphreton\DatabaseConnection
     */
    public function getDatabase($name) {
        if (!array_key_exists($name, $this->databases)) {
            throw new \Exception("Database $name does not exist");
        }
        return $this->databases[$name];
    }

    /**
     * Prevents constructor call
     * 
     * @return void
     */
    private function __construct() { }

    /**
     * Prevents the instance from being cloned
     * 
     * @return void
     */
    private function __clone() { }

    /**
     * Prevents the instance from being unserialized
     * 
     * @throws Exception always
     * 
     * @return void
     */
    public function __wakeup() { 
        throw new \Exception("Cannot unserialize singleton");
    }
}
