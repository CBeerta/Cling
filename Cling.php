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
* Register Autoloader
* Loads "Cling_*" Class files from the directory Cling.php is located in
**/
spl_autoload_register(array('Cling', 'autoload'));

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
    * @return self
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
        
        $route = new Cling_Route();
        $route->longopt($longopt)
            ->shortopt($shortopt)
            ->callable($command)
            ->help('');

        $this->_routes[] = $route;
        
        return $route;
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
        if ($value === null && isset($this->_options[$longopt])) {
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
    public function configure($file)
    {
        $config = parse_ini_file($file);

        foreach ($config as $k=>$v) {
            $this->option($k, $v);
        }
    }

    /**
    * Autoload classes
    *
    * @param string $class Class Name
    *
    * @return void
    **/
    public static function autoload($class)
    {
        if (strpos($class, 'Cling') !== 0) {
            return;
        }

        $file = dirname(__FILE__) 
            . '/' 
            . str_replace('_', DIRECTORY_SEPARATOR, substr($class, 5)) 
            . '.php';
            
        if ( file_exists($file) ) {
            include_once $file;
        }    
    }

    /**
    * Print Help
    *
    * @return void
    **/
    public function __toString()
    {   
        $str = "Usage: " . $this->appname . " [OPTION]...\n";
        
        foreach ($this->_routes as $route) {
            
            $str .= "\t";
            if ($route->shortopt()) {
                $str .= "-" . rtrim($route->shortopt(), ':') . ", ";
            } else {
                $str .= "    ";
            }
            
            // TODO: Help Text should align to longest longopt
            //       And should also replate linebreaks with correct position
            $str .= sprintf("--%-30s", rtrim($route->longopt(), ':'));
            
            $str .= "\t" . $route->help();
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
        if (PHP_SAPI !== 'cli') {
            throw new Exception("This is a Command Line Application.");
        }
        
        try 
        {
            /**
            * Go through all routes to collect short and longopts for getopt()
            **/
            $shortopts = '';
            $longopts = array();
            foreach ($this->_routes as $route) {
                $shortopts .= $route->shortopt();
                $longopts[] = $route->longopt();
            }
            
            $options = getopt($shortopts, $longopts);
            
            /**
            * Go through all routes, and execute commands
            **/
            $dispatched = false;
            foreach ($this->_routes as $route) {
                foreach ($options as $k=>$v) {
                    if (rtrim($route->shortopt(), ':') === $k 
                        || rtrim($route->longopt(), ':') === $k
                    ) {
                        $dispatched = $route->dispatch($v);
                        continue;
                    }
                }                
            }

            if (!$dispatched) {
                /**
                * No (valid) Options give.
                * FIXME: How to handle the help text?
                **/
                if (empty($options)) {
                    echo $this;
                    exit;
                }
            }
        } 
        catch (Exception $e) 
        {
            if ($this->option('debug')) {
                throw new Exception($e);
            } else {
                die("Application terminated unexpectedly.\n");
            }
        }
    }

}

