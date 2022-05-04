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

    public function __construct() {
        parent::__construct();
        $this->connection = \Aphreton\DatabasePool::getInstance()->getDatabase('main');
        $this->source_name = 'BOOK';
    }
}
