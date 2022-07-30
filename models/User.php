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
        /**
     * @var ?string
     */
    public $refresh_token = null;

    public function __construct() {
        parent::__construct();
        $this->connection = \Aphreton\DatabasePool::getInstance()->getDatabase('main');
        $this->source_name = 'USER';
    }
}
