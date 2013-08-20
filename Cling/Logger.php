<?php
/**
* Finally, a light, permissions-checking logging class.
*
* Originally written for use with wpSearch
*
* PHP Version 5.3
*
* Usage:
* $log = new KLogger('/var/log/', KLogger::INFO);
* $log->logInfo('Returned a million search results'); //Prints to the log file
* $log->logCrit('Oh dear.'); //Prints to the log file
* $log->logDebug('x = 5'); //Prints nothing due to current severity threshhold
*
* @category CLI
* @package  Cling
* @author   Kenny Katzgrau <katzgrau@gmail.com>
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     https://github.com/katzgrau/KLogger
*
* Copyright (c) 2008-2010 Kenny Katzgrau katzgrau@gmail.com
* Copyright (c) 2013 Claus Beerta
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*/

namespace Cling;

/**
* Logger Class
*
* @category CLI
* @package  Cling
* @author   Kenny Katzgrau <katzgrau@gmail.com>
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     https://github.com/katzgrau/KLogger
**/
class Logger
{
    /**
    * Error severity, from low to high. From BSD syslog RFC, secion 4.1.1
    * @link http://www.faqs.org/rfcs/rfc3164.html
    */
    const EMERG  = 0;  // Emergency: system is unusable
    const ALERT  = 1;  // Alert: action must be taken immediately
    const CRIT   = 2;  // Critical: critical conditions
    const ERR    = 3;  // Error: error conditions
    const WARN   = 4;  // Warning: warning conditions
    const NOTICE = 5;  // Notice: normal but significant condition
    const INFO   = 6;  // Informational: informational messages
    const DEBUG  = 7;  // Debug: debug messages

    /**
    * Log nothing at all
    */
    const OFF    = 8;

    /**
    * Internal status codes
    */
    const STATUS_LOG_OPEN    = 1;
    const STATUS_OPEN_FAILED = 2;
    const STATUS_LOG_CLOSED  = 3;

    /**
    * Logging Destinations
    */
    const LOG_FILE      = 1;
    const LOG_STDOUT    = 2;
    const LOG_STDERR    = 3;
    const LOG_ERROR_LOG = 4;

    /**
    * We need a default argument value in order to add the ability to easily
    * print out objects etc. But we can't use NULL, 0, FALSE, etc, because those
    * are often the values the developers will test for. So we'll make one up.
    */
    const NO_ARGUMENTS = 'KLogger::NO_ARGUMENTS';

    /**
    * Keep this amount of messages in the queue at most
    **/
    const MAX_MESSAGEQUEUE = 1000;

    /**
    * Holds messages generated by the class
    * @var array
    */
    private $_messageQueue      = array();

    /**
    * Log Destination
    * @var integer
    */
    private $_logDestination    = self::LOG_STDOUT;

    /**
    * Path to the log file
    * @var string
    */
    private $_logFilePath       = null;

    /**
    * Current minimum logging threshold
    * @var integer
    */
    private $_severityThreshold = self::INFO;

    /**
    * Standard messages produced by the class. Can be modified for il8n
    * @var array
    */
    private $_messages = array(
        'writefail'   => 'The file could not be written to. \
                         Check that appropriate permissions have been set.',
        'opensuccess' => 'The log file was opened successfully.',
        'openfail'    => 'The file could not be opened. Check permissions.',
    );

    /**
    * Default severity of log messages, if not specified
    * @var integer
    */
    private static $_defaultSeverity    = self::DEBUG;

    /**
    * Valid PHP date() format string for log timestamps
    * @var string
    */

    private static $_dateFormat         = 'r';

    /**
    * Array of KLogger instances, part of Singleton pattern
    * @var array
    */
    private static $_instances           = array();

    /**
    * Partially implements the Singleton pattern. Each $logDirectory gets one
    * instance.
    *
    * @param string  $logDirectory File path to the logging directory
    * @param integer $severity     One of the pre-defined severity constants
    *
    * @return KLogger
    */
    public static function instance($logDirectory = false, $severity = false)
    {
        if ($severity === false) {
            $severity = self::$_defaultSeverity;
        }
        
        if ($logDirectory === false) {
            if (count(self::$_instances) > 0) {
                return current(self::$_instances);
            } else {
                $logDirectory = dirname(__FILE__);
            }
        }

        if (in_array($logDirectory, self::$_instances)) {
            return self::$_instances[$logDirectory];
        }

        self::$_instances[$logDirectory] = new self($logDirectory, $severity);

        return self::$_instances[$logDirectory];
    }

    /**
    * Class constructor
    *
    * @param string  $logDirectory File path to the logging directory
    * @param integer $severity     One of the pre-defined severity constants
    * @param integer $destination  Destination for the Output to go to
    *
    * @return void
    */
    public function __construct($logDirectory, $severity, $destination)
    {
        $logDirectory = rtrim($logDirectory, '\\/');

        if ($severity === self::OFF) {
            return;
        }
        
        $this->_logDestination = $destination;

        $this->_logFilePath = $logDirectory
            . DIRECTORY_SEPARATOR
            . 'log_'
            . date('Y-m-d')
            . '.txt';
            
        if ($this->_logDestination === self::LOG_FILE) {

            $this->_severityThreshold = $severity;
            if (!file_exists($logDirectory)) {
                mkdir($logDirectory, 0777, true);
            }

            if (file_exists($this->_logFilePath) 
                && !is_writable($this->_logFilePath)
            ) {
                $this->_messageQueue[] = $this->_messages['writefail'];
                return;
            } else {
                $this->_messageQueue[] = $this->_messages['opensuccess'];
                return;
            }
            
        }
    }

    /**
    * Class destructor
    */
    public function __destruct()
    {
    }
    
    /**
    * Writes a $line to the log with a severity level of DEBUG
    *
    * @param string $line Information to log
    * @param int    $args Arguments
    *
    * @return void
    */
    public function debug($line, $args = self::NO_ARGUMENTS)
    {
        $this->log($line, self::DEBUG);
    }

    /**
    * Returns (and removes) the last message from the queue.
    *
    * @return string
    */
    public function getMessage()
    {
        return array_pop($this->_messageQueue);
    }

    /**
    * Returns the entire message queue (leaving it intact)
    *
    * @return array
    */
    public function getMessages()
    {
        return $this->_messageQueue;
    }

    /**
    * Empties the message queue
    *
    * @return void
    */
    public function clearMessages()
    {
        $this->_messageQueue = array();
    }

    /**
    * Sets the date format used by all instances of KLogger
    * 
    * @param string $dateFormat Valid format string for date()
    *
    * @return void
    */
    public static function setDateFormat($dateFormat)
    {
        self::$_dateFormat = $dateFormat;
    }

    /**
    * Writes a $line to the log with a severity level of INFO. Any information
    * can be used here, or it could be used with E_STRICT errors
    *
    * @param string $line Information to log
    * @param int    $args Arguments
    *
    * @return void
    */
    public function info($line, $args = self::NO_ARGUMENTS)
    {
        $this->log($line, self::INFO, $args);
    }

    /**
    * Writes a $line to the log with a severity level of NOTICE. Generally
    * corresponds to E_STRICT, E_NOTICE, or E_USER_NOTICE errors
    *
    * @param string $line Information to log
    * @param int    $args Arguments
    *
    * @return void
    */
    public function notice($line, $args = self::NO_ARGUMENTS)
    {
        $this->log($line, self::NOTICE, $args);
    }

    /**
    * Writes a $line to the log with a severity level of WARN. Generally
    * corresponds to E_WARNING, E_USER_WARNING, E_CORE_WARNING, or 
    * E_COMPILE_WARNING
    *
    * @param string $line Information to log
    * @param int    $args Arguments
    *
    * @return void
    */
    public function warn($line, $args = self::NO_ARGUMENTS)
    {
        $this->log($line, self::WARN, $args);
    }

    /**
    * Writes a $line to the log with a severity level of ERR. Most likely used
    * with E_RECOVERABLE_ERROR
    *
    * @param string $line Information to log
    * @param int    $args Arguments
    *
    * @return void
    */
    public function error($line, $args = self::NO_ARGUMENTS)
    {
        $this->log($line, self::ERR, $args);
    }

    /**
    * Writes a $line to the log with a severity level of ALERT.
    *
    * @param string $line Information to log
    * @param int    $args Arguments
    *
    * @return void
    */
    public function alert($line, $args = self::NO_ARGUMENTS)
    {
        $this->log($line, self::ALERT, $args);
    }

    /**
    * Writes a $line to the log with a severity level of CRIT.
    *
    * @param string $line Information to log
    * @param int    $args Arguments
    *
    * @return void
    */
    public function crit($line, $args = self::NO_ARGUMENTS)
    {
        $this->log($line, self::CRIT, $args);
    }

    /**
    * Writes a $line to the log with a severity level of EMERG.
    *
    * @param string $line Information to log
    * @param int    $args Arguments
    *
    * @return void
    */
    public function emerg($line, $args = self::NO_ARGUMENTS)
    {
        $this->log($line, self::EMERG, $args);
    }

    /**
    * Writes a $line to the log with the given severity
    *
    * @param string  $line     Text to add to the log
    * @param integer $severity Severity level of log message (use constants)
    * @param int     $args     Arguments
    *
    * @return void
    */
    public function log($line, $severity, $args = self::NO_ARGUMENTS)
    {
        if ($this->_severityThreshold >= $severity) {
            $status = $this->_getTimeLine($severity);
            
            $line = "$status $line";
            
            if ($args !== self::NO_ARGUMENTS) {
                /* Print the passed object value */
                $line = $line . '; ' . var_export($args, true);
            }
            
            $this->writeFreeFormLine($line . PHP_EOL);
        }
        
        if (count($this->_messageQueue) > self::MAX_MESSAGEQUEUE) {
            // Truncate Message Queue
            $this->_messageQueue = array_splice(
                $this->_messageQueue, 
                0,
                self::MAX_MESSAGEQUEUE
            );
        }
                
    }

    /**
    * Writes a line to the log without prepending a status or timestamp
    *
    * @param string $line Line to write to the log
    *
    * @return void
    */
    public function writeFreeFormLine($line)
    {
        if ($this->_severityThreshold === self::OFF) {
            return;
        }
    
        switch ($this->_logDestination) {
        case self::LOG_FILE:
            error_log($line, 3, $this->_logFilePath);
            break;
        case self::LOG_STDOUT:
            fwrite(STDOUT, $line);
            break;
        case self::LOG_STDERR:
            fwrite(STDERR, $line);
            break;
        case self::LOG_ERROR_LOG:
            return error_log($line);
            break;
        }
    }

    /**
    * Writes a line to the log without prepending a status or timestamp
    *
    * @param integer $level Severity level of log message (use constants)
    *
    * @return string
    */
    private function _getTimeLine($level)
    {
        $time = gmdate(self::$_dateFormat);
        
        $levelText = array(
            self::EMERG => 'EMERG',
            self::ALERT => 'ALERT',
            self::CRIT => 'CRIT',
            self::NOTICE => 'NOTICE',
            self::INFO => 'INFO',
            self::DEBUG => 'DEBUG',
            self::ERR => 'ERROR'
        );
        
        return sprintf("%s [%6s]:", $time, $levelText[$level]);
    }
}
