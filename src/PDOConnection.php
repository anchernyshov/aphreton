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
     * Checks database connection
     * 
     * @return bool
     */
    public function checkConnection() {
        try {
            $this->query('SELECT 1', []);
            return true;
        } catch (\Exception $e) {
            return false;
        }   
    }

    /**
     * Lazy loads and queries the database with given sql string with given parameters
     * 
     * @param string $sql Query string
     * @param array $params Parameters
     * 
     * @throws \Aphreton\APIException if PDO error occurs
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
            throw new \Aphreton\APIException(
                'PDO connection exception: ' . $e->getMessage(),
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR
            );
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
    public function find($source, $params = null, $order_by = null, $limit = null, $offset = null) {
        $sql = 'SELECT * FROM ' . $source;
        $conditions = [];
        $sql_params = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                //WHERE x IN (:x1, :x2, ...)
                $tmp = $key . ' IN (';
                foreach ($value as $inner_key => $inner_value) {
                    $tmp .= ':' . $key . strval($inner_key) . ',';
                    $sql_params[$key . strval($inner_key)] = $inner_value;
                }
                //remove last comma
                $tmp = substr($tmp, 0, -1);
                $tmp .= ')';
                $conditions[] = $tmp;
            } else {
                //WHERE x = :x
                $conditions[] = $key . ' = :' . $key;
                $sql_params[$key] = $value;
            }
        }
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
        if ($order_by) {
            $sql .= ' ORDER BY :order_by';
            $sql_params['order_by'] = $order_by;
        }
        if ($limit) {
            $sql .= ' LIMIT :limit';
            $sql_params['limit'] = $limit;
        }
        if ($offset && $order_by) {
            $sql .= ' OFFSET :offset';
            $sql_params['offset'] = $offset;
        }
        return $this->query($sql, $sql_params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Inserts record in the database table
     * 
     * @param string $source Table name
     * @param array $data Data to insert
     * 
     * @throws \Aphreton\APIException if $data array is empty
     * @throws \Aphreton\APIException if query row count equals 0 (no data was inserted)
     * 
     * @return int Inserted id
     */
    public function insert(string $source, array $data) {
        if (!$data) {
            throw new \Aphreton\APIException(
                'Attempt to perform PDO record creation with empty data',
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR
            );
        }
        $sql = "INSERT INTO {$source} ";
        $data_keys = array_keys($data);
        $data_prefixed_keys = preg_filter('/^/', ':', $data_keys);
        $sql .= '(' . implode(',', $data_keys) . ') VALUES (' . implode(',', $data_prefixed_keys) . ')';
        if ($this->query($sql, $data)->rowCount() > 0) {
            return $this->pdo->lastInsertId();
        } else {
            throw new \Aphreton\APIException(
                'PDO insert error. Table:' . $source . ', query: ' . $sql,
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR
            );
        }
    }

    /**
     * Updates record in the database table
     * 
     * @param string $source Table name
     * @param array $filter Search parameters
     * @param array $data Data to update
     * 
     * @throws \Aphreton\APIException if $filter or $data arrays are empty
     * 
     * @return bool Operation status
     */
    public function update(string $source, array $filter, array $data) {
        if (!empty($filter) && !empty($data)) {
            $sql = 'UPDATE ' . $source . ' SET ';

            $values = [];
            foreach ($data as $key => $value) {
                $values[] = $key . ' = :' . $key;
            }
            $conditions = [];
            foreach ($filter as $key => $value) {
                $conditions[] = $key . ' = :' . $key;
            }

            $sql .= implode(',', $values) . ' WHERE ' . implode(' AND ', $conditions);
            //Manually adding $_id field value to PDO prepared statement arguments
            if (array_key_exists('_id', $filter)) {
                $data['_id'] = $filter['_id'];
            }
            return ($this->query($sql, $data)->rowCount() > 0);
        } else {
            throw new \Aphreton\APIException(
                'Attempt to perform PDO record update with empty filter/data',
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR
            );
        }
    }

    /**
     * Deletes record in the database table
     * 
     * @param string $source Table name
     * @param array $filter Search parameters
     * 
     * @throws \Aphreton\APIException if $filter array is empty
     * 
     * @return bool Operation status
     */
    public function delete(string $source, array $filter) {
        if (!empty($filter)) {
            $sql = 'DELETE FROM ' . $source;
            $conditions = [];
            foreach ($filter as $key => $value) {
                $conditions[] = $key . ' = :' . $key;
            }

            $sql .= ' WHERE ' . implode(' AND ', $conditions);
            //Manually adding $_id field value to PDO prepared statement arguments
            if (array_key_exists('_id', $filter)) {
                $data['_id'] = $filter['_id'];
            }
            return ($this->query($sql, $data)->rowCount() > 0);
        } else {
            throw new \Aphreton\APIException(
                'Attempt to perform PDO record deletion with empty filter',
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR
            );
        }
    }
}
