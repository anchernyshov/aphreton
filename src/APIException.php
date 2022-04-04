<?php

namespace Aphreton;

/**
 * API Exception class
 * 
 * Contains log information for API administrator and error message to return to user in response "error" field
 */
class APIException extends \Exception {

    /**
     * @var int
     */
    public const ERROR_TYPE_DEFAULT = 0;
    /**
     * @var int
     */
    public const ERROR_TYPE_AUTH = 1;
    /**
     * @var int
     */
    public const ERROR_TYPE_NOT_FOUND = 2;

    /**
     * Message to write to log
     * @var string
     */
    private $log_message;
    /**
     * Log entry level (see \Aphreton\Models\LogEntry LOG_LEVEL constants)
     * @var int
     */
    private $log_level;

    /**
     * Constructs the exception
     * 
     * @param string $log_message
     * @param int $log_level (optional) \Aphreton\Models\LogEntry::LOG_LEVEL_INFO by default
     * @param string $user_message (optional) 'API Error' by default
     * @param int $code (optional) self::ERROR_TYPE_DEFAULT by default
     * 
     * @return void
     */
    public function __construct($log_message, $log_level = null, $user_message = null, $code = null, \Throwable $previous = null) {
        if (!$log_level) {
            $log_level = \Aphreton\Models\LogEntry::LOG_LEVEL_INFO;
        }
        if (!$user_message) {
            $user_message = 'API Error';
        }
        if (!$code) {
            $code = self::ERROR_TYPE_DEFAULT;
        }
        parent::__construct($user_message, $code, $previous);
        $this->log_message = $log_message;
        $this->log_level = $log_level;
    }

    /**
     * Getter for $this->log_message
     * 
     * @return string
     */
    public function getLogMessage() {
        return $this->log_message;
    }

    /**
     * Getter for $this->log_level
     * 
     * @return int
     */
    public function getLogLevel() {
        return $this->log_level;
    }
}