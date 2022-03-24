<?php

namespace Aphreton;

class DatabasePool {
	
    private static $instance;
	private $databases = array();
	
	private function __construct() { }
	
	public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new DatabasePool();
		}
        return self::$instance;
    }
	
	public function addDatabase(string $name, string $dsn, string $user, string $password) {
		$this->databases[$name] = new \Aphreton\DatabaseConnection($dsn, $user, $password);
	}

    public function getDatabase($name) {
        if (!array_key_exists($name, $this->databases)) {
            throw new \Exception("Database $name does not exist");
		}
        return $this->databases[$name];
    }
}