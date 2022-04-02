<?php

namespace Aphreton;

/**
 * Represents a PDO connection
 * 
 * Database connection lazy initialization occurs after the first call of query method
 */
class PDOConnection extends DatabaseConnection {

    /**
     * PDO connection object
     * @var \PDO
     */
    private $pdo;

    public function __construct(string $dsn, string $username, string $password) {
        parent::__construct($dsn, $username, $password);
    }

    /**
     * Lazy loads and queries the database with given sql string with given parameters
     * 
     * @param string $sql Query string
     * @param array $params Parameters
     * 
     * @throws Exception if PDO error occurs
     * 
     * @return object
     */
    public function query(string $sql, $params = null) {
        if (is_null($this->pdo)) {
            $this->pdo = new \PDO($this->dsn, $this->username, $this->password);
            $this->pdo->setAttribute( \PDO::ATTR_EMULATE_PREPARES, false );
            $this->pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
            $this->pdo->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC ); 
        }
        try {
            $stmt = $this->pdo->prepare($sql); 
            $stmt->execute($params);
            return $stmt;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
