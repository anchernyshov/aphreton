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

    /* TODO: Main database preparation on first launch
        CREATE TABLE "USERS" (
            "id"	INTEGER NOT NULL UNIQUE,
            "login"	TEXT NOT NULL UNIQUE,
            "password"	TEXT NOT NULL,
            PRIMARY KEY("id" AUTOINCREMENT)
        );
    */

    public function __construct() {
        parent::__construct();
        $this->connection = \Aphreton\DatabasePool::getInstance()->getDatabase('test');
        $this->table_name = 'USERS';
    }
}
