<?php

namespace Aphreton\Models;

/**
 * Represents log entry
 */
class LogEntry extends \Aphreton\Model {

    /**
     * @var int
     */
    public const LOG_LEVEL_INFO = 0;
    /**
     * @var int
     */
    public const LOG_TYPE_WARNING = 1;
    /**
     * @var int
     */
    public const LOG_TYPE_ERROR = 2;
    /**
     * @var \MongoDB\BSON\ObjectId
     */
    private $_id = null;
    /**
     * @var int
     */
    public int $level;
    /**
     * @var string
     */
    public string $message;
    /**
     * @var string
     */
    public string $timestamp;

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
