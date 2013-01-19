<?php

/**
 * Logging library for use with Logentries
 *
 * Usage:
 * $log = LeLogger::getLogger('mylogger', 'ad43g-dfd34-df3ed-3d3d3');
 * $log->Info("I'm an informational message");
 * $log->Warn("I'm a warning message");
 *
 * Design inspired by KLogger library which is available at
 *   https://github.com/katzgrau/KLogger.git
 *
 * @author Mark Lacomber <marklacomber@gmail.com>
 * @version 1.2
 */

class LeLogger
{
    //Some standard log levels
    const ERROR = 0;
    const WARN = 1;
    const NOTICE = 2;
    const INFO = 3;
    const DEBUG = 4;

    const STATUS_SOCKET_OPEN = 1;
    const STATUS_SOCKET_FAILED = 2;
    const STATUS_SOCKET_CLOSED = 3;

    // Logentries server address for receiving logs
    const LE_ADDRESS = 'api.logentries.com';
    // Logentries server port for receiving logs by token
    const LE_PORT = 10000;

    private $_socket = null;

    private $_socketStatus = self::STATUS_SOCKET_CLOSED;

    private $_defaultSeverity = self::DEBUG;

    private $_severityThreshold = self::INFO;

    private $_loggerName = null;

    private $_logToken = null;

    private static $_timestampFormat = 'Y-m-d G:i:s';

    private $_use_tcp = true;

    private function parse_args($args, $defaults = '')
    {
        if ( is_object( $args ) )
            $r = get_object_vars( $args );
        elseif ( is_array( $args ) )
            $r =& $args;
        else //unsupported format
            return array();

        if ( is_array( $defaults ) )
            return array_merge( $defaults, $r );
        return $r;
    }

    public function __construct($params)
    {
        $default_params = array(
            'logger_name' => 'Default',
            'token' => '',
            'use_tcp' => true,
            'severity' => false);

        $args = parse_args( $params, $default_params);

        extract( $args, EXTR_SKIP );

        if ($severity === false)
        {
            $this->_severityThreshold = self::DEBUG;
        }

        $this->_loggerName = $logger_name;

        $this->_logToken = $token;

        $this->_severityThreshold = $severity;

        $this->_use_tcp = $use_tcp;
    }

    public function __destruct()
    {
        if ($this->_socket != null) {
            socket_close($this->_socket);
            $this->_socketStatus = self::STATUS_SOCKET_CLOSED;
        }
    }

    public function _createSocket()
    {
        //Make socket
        try{

            if ($this->_use_tcp === true)
            {
                $this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            }
            else{
                $this->_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            }

            if ($this->_socket === false)
            {
                trigger_error("Could not create socket for Logentries Logger, reason: " . socket_strerror(socket_last_error()), E_USER_ERROR);
                $this->_socketStatus = self::STATUS_SOCKET_FAILED;
                return;
            }

            $result = socket_connect($this->_socket, self::LE_ADDRESS, self::LE_PORT);

            if ($result === false)
            {
                trigger_error("Could not connect to Logentries, reason: " . socket_strerror(socket_last_error()), E_USER_ERROR);
                $this->_socketStatus = self::STATUS_SOCKET_FAILED;
                return;
            }

            socket_set_nonblock($this->_socket);

            $this->_socketStatus = self::STATUS_SOCKET_OPEN;
        }catch(Exception $ex){
            trigger_error("Error connecting to Logentries, reason: " . $ex->getMessage(), E_USER_ERROR);
            $this->_socketStatus = self::STATUS_SOCKET_FAILED;
        }
    }

    public function Debug($line)
    {
        $this->log($line, self::DEBUG);
    }

    public function Info($line)
    {
        $this->log($line, self::INFO);
    }

    public function Warn($line)
    {
        $this->log($line, self::WARN);
    }

    public function Error($line)
    {
        $this->log($line, self::ERROR);
    }

    public function Notice($line)
    {
        $this->log($line, self::NOTICE);
    }

    public function log($line, $severity)
    {
        if ($this->_socket === null)
        {
            $this->_createSocket();
        }

        if ($this->_severityThreshold >= $severity) {
            $prefix = $this->_getTime($severity);

            $line = $prefix . $line;

            $this->writeToSocket($line . PHP_EOL);
        }
    }

    public function writeToSocket($line)
    {
        if ($this->_socketStatus == self::STATUS_SOCKET_OPEN)
        {
            $finalLine = $this->_logToken . $line;
            socket_write($this->_socket, $finalLine, strlen($finalLine));
        }
    }

    private function _getTime($level)
    {
        $time = date(self::$_timestampFormat);

        switch ($level) {
            case self::INFO:
                return "$time  INFO - ";
            case self::WARN:
                return "$time - WARN - ";
            case self::ERROR:
                return "$time - ERROR - ";
            case self::NOTICE:
                return "$time - NOTICE - ";
            case self::DEBUG:
                return "$time - DEBUG - ";
            default:
                return "$time - LOG - ";
        }
    }
}
?>
