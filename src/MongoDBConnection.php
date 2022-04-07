<?php

namespace Aphreton;

/**
 * Represents a MongoDB connection
 * 
 * Database connection lazy initialization occurs after the first public method call
 */
class MongoDBConnection extends DatabaseConnection {

    /**
     * MongoDB client object
     * @var \MongoDB\Driver\Manager
     */
    private $client;

    public function __construct(string $dsn, string $username, string $password) {
        parent::__construct($dsn, $username, $password);
    }

    /**
     * Lazy loads database connection
     * 
     * @return void
     */
    private function lazyLoadClient() {
        if (is_null($this->client)) {
            $this->client = new \MongoDB\Driver\Manager($this->dsn, ['username' => $this->username, 'password' => $this->password]);
        }
    }

    /**
     * Queries the database with given filter and options
     * 
     * @param string $source Formatted string "DATABASE_NAME.COLLECTION_NAME"
     * @param array $filter
     * @param array $options
     * 
     * @throws \Aphreton\APIException if MongoDB driver error occurs
     * 
     * @return object
     */
    public function query(string $source, array $filter, $options = []) {
        $this->lazyLoadClient();
        try {
            $query = new \MongoDB\Driver\Query($filter, $options);
            $cursor = $this->client->executeQuery($source, $query);
            return $cursor;
        } catch (\Exception $e) {
            throw new \Aphreton\APIException(
                'MongoDB connection exception: ' . $e->getMessage(),
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR
            );
        }
    }

    /**
     * Inserts new entry into database collection with given name
     * 
     * @param string $source Formatted string "DATABASE_NAME.COLLECTION_NAME"
     * @param array $data Data to insert
     * 
     * @throws \Aphreton\APIException if MongoDB driver error occurs
     * @throws \Aphreton\APIException if $data is empty
     * 
     * @return \MongoDB\BSON\ObjectId
     */
    public function insert(string $source, array $data) {
        $this->lazyLoadClient();
        if (!empty($data)) {
            try {
                $bulk = new \MongoDB\Driver\BulkWrite();
                $oid = $bulk->insert($data);
                $this->client->executeBulkWrite($source, $bulk);
                return $oid;
            } catch (\Exception $e) {
                throw new \Aphreton\APIException(
                    'MongoDB connection exception: ' . $e->getMessage(),
                    \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR
                );
            }
        } else {
            throw new \Aphreton\APIException(
                'Attempt to perform mongodb record creation with empty data',
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR
            );
        }
    }

    /**
     * Updates a database entry with given data
     * 
     * @param string $source Formatted string "DATABASE_NAME.COLLECTION_NAME"
     * @param array $filter Search parameters
     * @param array $data Data to update
     * 
     * @throws \Aphreton\APIException if MongoDB driver error occurs
     * @throws \Aphreton\APIException if $filter or $data is empty
     * 
     * @return \MongoDB\Driver\WriteResult
     */
    public function update(string $source, array $filter, array $data) {
        $this->lazyLoadClient();
        if (!empty($filter) && !empty($data)) {
            try {
                $bulk = new \MongoDB\Driver\BulkWrite();
                $bulk->update($filter, ['$set' => $data], ['multi' => false, 'upsert' => false]);
                $result = $this->client->executeBulkWrite($source, $bulk);
                return $result;
            } catch (\Exception $e) {
                throw new \Aphreton\APIException(
                    'MongoDB connection exception: ' . $e->getMessage(),
                    \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR
                );
            }
        } else {
            throw new \Aphreton\APIException(
                'Attempt to perform mongodb record update with empty filter/data',
                \Aphreton\Models\LogEntry::LOG_LEVEL_ERROR
            );
        }
    }
}
