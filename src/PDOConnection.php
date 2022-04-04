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
     * @throws \Exception if PDO error occurs
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
            throw new \Aphreton\APIException($e->getMessage(), 'API database error');
        }
    }

    /**
     * Finds records in the database table
     * 
     * @param string $source Table name
     * @param array $params Search parameters
     * 
     * @return null|array
     */
    public function find($source, $params = null) {
        $sql = "SELECT * FROM {$source}";
        $conditions = [];
        foreach ($params as $key => $value) {
            $conditions[] = "{$key} = :{$key}";
        }
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
        return $this->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Inserts record in the database table
     * 
     * @param string $source Table name
     * @param array $data Data to insert
     * 
     * @throws \Exception if $data array is empty
     * @throws \Exception if query row count equals 0 (no data was inserted)
     * 
     * @return int Inserted id
     */
    public function insert(string $source, array $data) {
        if (!$data) {
            throw new \Exception('Cannot create empty record');
        }
        $sql = "INSERT INTO {$source} ";
        $keys = array_keys($data);
        $values = [];
        foreach ($data as $key => $value) {
            $values[] = ':' . $key;
        }
        $sql .= '(' . implode(',', $keys) . ') VALUES (' . implode(',', $values) . ')';
        if ($this->query($sql, $data)->rowCount() > 0) {
            return $this->pdo->lastInsertId();
        } else {
            throw new \Exception('Database insert error');
        }
    }

    /**
     * Updates record in the database table
     * 
     * @param string $source Table name
     * @param array $filter Search parameters
     * @param array $data Data to update
     * 
     * @throws \Exception if $filter or $data arrays are empty
     * 
     * @return bool Operation status
     */
    public function update(string $source, array $filter, array $data) {
        if (!empty($filter) && !empty($data)) {
            $sql = "UPDATE {$source} SET ";

            $values = [];
            foreach ($data as $key => $value) {
                $values[] = "{$key} = :{$key}";
            }
            $conditions = [];
            foreach ($filter as $key => $value) {
                $conditions[] = "{$key} = :{$key}";
            }

            $sql .= implode(',', $values) . ' WHERE ' . implode(' AND ', $conditions);
            //Manually adding $_id field value to PDO prepared statement arguments
            if (array_key_exists('_id', $filter)) {
                $data['_id'] = $filter['_id'];
            }
            return ($this->query($sql, $data)->rowCount() > 0);
        } else {
            throw new \Exception('Cannot update record with given parameters');
        }
    }
}
