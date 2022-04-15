<?php

namespace Aphreton\Models;

/**
 * Example model
 * 
 * @property \Aphreton\PDOConnection $connection
 */
class Book extends \Aphreton\Model {

    /**
     * @var ?string
     */
    public $name = null;
    /**
     * @var ?int
     */
    public $author_id = null;
    /**
     * @var int
     */
    public $price = 0;

    /*
        CREATE TABLE "BOOK" (
            "_id" INTEGER NOT NULL UNIQUE,
            "name" TEXT NOT NULL,
            "author_id" INTEGER NOT NULL,
            "price" INTEGER NOT NULL,
            PRIMARY KEY("_id" AUTOINCREMENT)
        );
    */

    public function __construct() {
        parent::__construct();
        $this->connection = \Aphreton\DatabasePool::getInstance()->getDatabase('test');
        $this->source_name = 'BOOK';
    }
}
