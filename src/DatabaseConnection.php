<?php

namespace Aphreton;

/**
 * Represents a database connection
 * 
 * Database connection lazy initialization occurs after the first call of query method
 */
class DatabaseConnection {

    /**
     * PDO connection object
     * @var \PDO
     */
    private $pdo;
    /**
     * Data source name
     * @var string
     */
    private string $dsn;
    /**
     * Database username
     * @var string
     */
    private string $username;
    /**
     * Password for database user $username
     * @var string
     */
    private string $password;

    public function __construct(string $dsn, string $username, string $password) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Lazy loads and queries the database with given sql string with given parameters
     * 
     * @param string $sql Query string
     * @param array $params Parameters
     * 
     * @throws Exception if PDO error occures
     * 
     * @return array
     */
    public function query(string $sql, $params = null) {
        $result = [];
        if (is_null($this->pdo)) {
            $this->pdo = new \PDO($this->dsn, $this->username, $this->password);
            $this->pdo->setAttribute( \PDO::ATTR_EMULATE_PREPARES , false );
            $this->pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
        }
        try {
            $stmt = $this->pdo->prepare($sql); 
            $stmt->execute($params);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        return $result;
    }
}
