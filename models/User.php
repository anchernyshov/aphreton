<?php

namespace Aphreton\Models;

/**
 * Represents API user
 */
class User extends \Aphreton\Model {

    /**
     * @var int
     */
    public int $id;
    /**
     * @var string
     */
    public string $login;
    /**
     * @var string
     */
    public string $password;
    /**
     * @var string
     */
    public string $last_logined;

    /* TODO: Main database preparation on first launch
        CREATE TABLE "USERS" (
            "id" INTEGER NOT NULL UNIQUE,
            "login" TEXT NOT NULL UNIQUE,
            "password" TEXT NOT NULL,
            "last_logined" TEXT,
            PRIMARY KEY("id" AUTOINCREMENT)
        );
    */

    public function __construct() {
        parent::__construct();
        $this->connection = \Aphreton\DatabasePool::getInstance()->getDatabase('test');
        $this->table_name = 'USERS';
    }

    /**
     * Sets last_logined field value to current timestamp
     * 
     * @return void
     */
    public function updateLastLogined() {
        $result = $this->connection->query(
            'UPDATE USERS SET last_logined = :last_logined WHERE id = :id',
            ['last_logined' => date('Y-m-d H:i:s'), 'id' => $this->id]
        )->rowCount();
    }
}
