<?php
/**
* Cling - Microframework for CLI Applications
*
* PHP Version 5.3
*
* Copyright (C) 2011 by Claus Beerta
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
*
* @category CLI
* @package  Cling
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/

/**
* Cling - Microframework for CLI Applications
*
* @category CLI
* @package  Cling
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Cling
{
    /**
    * What the app is called ($_SERVER['argv'][0])
    **/
    public $appname;

    /**
    * List of Short Options of all commands or options
    **/    
    private $_shortopts = array();
    
    /**
    * List of Long Options of all commands or options
    **/    
    private $_longopts = array();
    
    /**
    * The Routes
    **/    
    private $_routes = array();
    
    /**
    * The Options
    **/    
    private $_options = array();

    /**
    * Constructor
    *
    * @param array $options User Options
    *
    * @return void
    **/
    public function __construct($options = array())
    {
        $this->appname = $_SERVER['argv'][0];
        set_error_handler(array('Cling', 'handleErrors'));

        $this->_options = array_merge(
            array(
                'debug' => false,
                ), 
            $options
        );
    }

    /**
    * Cling Error Handler
    *
    * @param int    $errno   Error Numer
    * @param string $errstr  Error String
    * @param string $errfile File that contains error
    * @param string $errline Line in File that caused error
    *
    * @return void
    **/
    public static function handleErrors(
        $errno, 
        $errstr = '', 
        $errfile = '', 
        $errline = '' 
    ) {
        if ( error_reporting() & $errno ) {
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        }
        return true;
    }
    
    /**
    * Add route for a command
    *
    * ARGUMENTS:
    * 
    * first: Long Option (REQUIRED)
    * second: Short Option or Command (REQUIRED)
    * third: Command (OPTIONAL)
    *
    * @param mixed $args See Comment for arguments
    *
    * @return void
    **/
    public function command($args = false)
    {
        $args = func_get_args();
        
        $longopt = array_shift($args);
        $t = array_shift($args);
        
        if (is_callable($t)) {
            $shortopt = '';
            $command = $t;
        } else {    
            $shortopt = $t;
            $command = array_shift($args);
        }

        if (!is_callable($command)) {
            throw new Exception("Command not Callable");
        }
        
        $this->_routes[] = array(
            'longopt' => $longopt,
            'shortopt' => $shortopt,
            'command' => $command,
        );
        
        $this->_shortopts[] = $shortopt;
        $this->_longopts[] = $longopt;
    }
    
    /**
    * Add a option
    *
    * @param string $longopt Long Option
    * @param mixed  $value   What Value to use for this Option
    *
    * @return mixed
    **/
    public function option($longopt, $value = null)
    {
        if ($value === null) {
            return $this->_options[$longopt];
        }
        
        $this->_options[$longopt] = $value;
    }

    /**
    * Source Options from a INI File
    *
    * @param string $file INI File
    *
    * @return mixed
    **/
    public function config($file)
    {
        $config = parse_ini_file($file);

        foreach ($config as $k=>$v) {
            $this->option($k, $v);
        }
    }

    /**
    * Print Info about Cling
    *
    * @return void
    **/
    public function __toString()
    {   
        $str = "Usage: ".$this->appname." [OPTION]...\n";
        
        foreach ($this->_longopts as $k=>$v) {
            
            $str .= "\t";
            if (!empty($this->_shortopts[$k])) {
                $str .= "-" . rtrim($this->_shortopts[$k], ':') . ", ";
            } else {
                $str .= "    ";
            }
            $str .= "--" . rtrim($v, ':');
            
            $str .= "\n";
        }

        return $str;
    }

    /**
    * Run
    *
    * @return void
    **/
    public function run()
    {
        try 
        {
            $options = getopt(implode($this->_shortopts), $this->_longopts);

            $executed = false;
            
            /**
            * Go through all routes, and execute commands
            **/
            foreach ($this->_routes as $route) {
                foreach ($options as $k=>$v) {
                    if (rtrim($route['shortopt'], ':') === $k 
                        || rtrim($route['longopt'], ':') === $k
                    ) {
                        $route['command']($v);
                        $executed = true;
                        continue;
                    }
                }                
            }
        } 
        catch (Exception $e) 
        {
        }
    }


}



