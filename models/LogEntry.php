<?php

namespace Aphreton\Models;

/**
 * Represents log entry
 * 
 * @property \Aphreton\MongoDBConnection $connection
 */
class LogEntry extends \Aphreton\Model {

    /**
     * @var int
     */
    public const LOG_LEVEL_INFO = 0;
    /**
     * @var int
     */
    public const LOG_LEVEL_WARNING = 1;
    /**
     * @var int
     */
    public const LOG_LEVEL_ERROR = 2;
    /**
     * @var int
     */
    public $level = 0;
    /**
     * @var ?string
     */
    public $message = null;
    /**
     * @var ?string
     */
    public $timestamp = null;

    public function __construct() {
        parent::__construct();
        $this->connection = \Aphreton\DatabasePool::getInstance()->getDatabase('logs');
        $this->source_name = 'aphreton.logs';
    }

    public static function create($message, $level = null) {
        $entry = new LogEntry();
        $entry->level = $level ? $level : self::LOG_LEVEL_INFO;
        $entry->message = $message;
        $entry->timestamp = date('Y-m-d H:i:s');
        $entry->save();
        unset($entry);
    }
}
