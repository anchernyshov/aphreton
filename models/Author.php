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

    public function __construct() {
        parent::__construct();
        $this->connection = \Aphreton\DatabasePool::getInstance()->getDatabase('main');
        $this->source_name = 'AUTHOR';
    }
}
