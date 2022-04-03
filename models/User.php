<?php

namespace Aphreton\Models;

/**
 * Represents API user
 * 
 * @property \Aphreton\PDOConnection $connection
 */
class User extends \Aphreton\Model {

    /**
     * @var ?string
     */
    public $login = null;
    /**
     * @var ?string
     */
    public $password = null;
    /**
     * @var int
     */
    public $level = 0;
    /**
     * @var ?string
     */
    public $last_logined = null;

    /* TODO: Main database preparation on first launch
        CREATE TABLE "USERS" (
            "_id" INTEGER NOT NULL UNIQUE,
            "login" TEXT NOT NULL UNIQUE,
            "password" TEXT NOT NULL,
            "level" INTEGER NOT NULL DEFAULT 0,
            "last_logined" TEXT,
            PRIMARY KEY("id" AUTOINCREMENT)
        );
    */

    public function __construct() {
        parent::__construct();
        $this->connection = \Aphreton\DatabasePool::getInstance()->getDatabase('test');
        $this->source_name = 'USERS';
    }
}
