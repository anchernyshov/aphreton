<?php

namespace Aphreton;

/**
 * Base class for a database connection
 */
abstract class DatabaseConnection {

    /**
     * Data source name
     * @var string
     */
    protected string $dsn;
    /**
     * Database username
     * @var string
     */
    protected string $username;
    /**
     * Password for database user $username
     * @var string
     */
    protected string $password;

    public function __construct(string $dsn, string $username, string $password) {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
    }

    public function checkConnection() { }

    public function query(string $filter, array $options, string $source) { }

    public function insert(array $data, string $source) { }

    public function update(array $filter, array $data, string $source) { }

    public function delete(array $filter, string $source) { }
}
