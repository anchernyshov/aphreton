<?php

namespace Aphreton\Models;

/**
 * Example model
 * 
 * @property \Aphreton\PDOConnection $connection
 */
class Author extends \Aphreton\Model {

    /**
     * @var ?string
     */
    public $name = null;

    /*
        CREATE TABLE "AUTHOR" (
            "_id" INTEGER NOT NULL UNIQUE,
            "name" TEXT NOT NULL,
            PRIMARY KEY("_id" AUTOINCREMENT)
        );
    */

    public function __construct() {
        parent::__construct();
        $this->connection = \Aphreton\DatabasePool::getInstance()->getDatabase('test');
        $this->source_name = 'AUTHOR';
    }
}
