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
     * @throws \Aphreton\APIException if database type is not in self::ALLOWED_DATABASE_TYPES
     * 
     * @return void
     */
    public function addDatabase(string $name, string $dsn, string $user, string $password) {
        $type = explode(':', $dsn)[0];
        if (in_array($type, self::ALLOWED_DATABASE_TYPES)) {
            if ($type === 'sqlite') {
                $this->databases[$name] = new PDOConnection($dsn, $user, $password);
            } else if ($type === 'mongodb') {
                $this->databases[$name] = new MongoDBConnection($dsn, $user, $password);
            }
        } else {
            throw new APIException(
                'Database type ' . $type . ' is not allowed',
                Models\LogEntry::LOG_LEVEL_ERROR
            );
        }
    }

    /**
     * Retrieves database with given name from the pool
     * 
     * @param string $name Database name
     * 
     * @throws \Aphreton\APIException if database with given name not exists
     * 
     * @return \Aphreton\DatabaseConnection
     */
    public function getDatabase($name) {
        if (!array_key_exists($name, $this->databases)) {
            throw new APIException(
                'Database ' . $name . ' does not exist',
                Models\LogEntry::LOG_LEVEL_ERROR
            );
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
     * @throws \Aphreton\APIException always
     * 
     * @return void
     */
    public function __wakeup() { 
        throw new APIException(
            'Cannot unserialize database pool singleton',
            Models\LogEntry::LOG_LEVEL_ERROR
        );
    }
}
