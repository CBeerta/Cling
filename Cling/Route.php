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

namespace Cling;

/**
* Cling_Route
*
* @category CLI
* @package  Cling
* @author   Claus Beerta <claus@beerta.de>
* @license  http://www.opensource.org/licenses/mit-license.php MIT License
* @link     http://claus.beerta.de/
**/
class Route
{
    /**
    * Long Option Name
    **/
    protected $longopt;
    
    /**
    * Short Option Name
    **/
    protected $shortopt;
    
    /**
    * Help Text
    **/
    protected $helptext;
    
    /**
    * Callable to execute
    **/
    protected $is_callable;
    
    /**
    * Wether or not this route is actually an option
    **/
    protected $isOption = false;

    /**
    * FP to stdin if needed
    **/
    private $_stdin = false;
    
    /**
    * Constructor
    *
    * @return void
    **/
    public function __construct() 
    {
    }
    
    /**
    * Get/Set the Long Command
    *
    * @param string $longopt long option name
    *
    * @return void
    **/
    public function longopt($longopt = null)
    {
        if ($longopt === null) {
            return $this->longopt;
        }
        
        switch ($longopt) {
        
        case ':*':
            $this->isOption = false;
            break;
        
        case ':stdin':
            $this->_stdin = fopen('php://stdin', 'r');
            if (!$this->_stdin) {
                throw new Exception("Unable to open STDIN");
            }
            $this->isOption = false;
            break;
            
        default:
            $this->isOption = true;
            break;
        }

        $this->longopt = $longopt;
        return $this;
    }
    
    /**
    * Get/Set the Short Command
    *
    * @param string $shortopt short option name
    *
    * @return void
    **/
    public function shortopt($shortopt = null)
    {
        if ($shortopt === null) {
            return $this->shortopt;
        }
        $this->shortopt = $shortopt;
        return $this;
    }
    
    /**
    * Get/Set the Help Text Command
    *
    * @param string $helptext Helptext for this command
    *
    * @return void
    **/
    public function help($helptext = null)
    {
        if ($helptext === null) {
            return $this->helptext;
        }
        $this->helptext = $helptext;
        return $this;
    }

    /**
    * Get/Set the Help Text Command
    *
    * @param string $is_callable Callable for this route
    *
    * @return void
    **/
    public function is_callable($is_callable = null)
    {
        if ($is_callable === null) {
            return $this->is_callable;
        }
        $this->is_callable = $is_callable;
        return $this;
    }

    /**
    * Execute the command
    *
    * @param string $value Value for command
    *
    * @return void
    **/
    public function dispatch($value = array())
    {
        if (is_callable($this->is_callable)) {
            call_user_func($this->is_callable, $value);
            return true;
        }
        return false;        
    }

    /**
    * Read Stdin and return line by line
    *
    * @return string
    **/
    public function readStdin()
    {
        if (feof($this->_stdin)) {
            return false;
        }
        return fgets($this->_stdin);
    }
    
    /**
    * Wether this route is a option or not
    *
    * @return bool
    **/
    public function isOption()
    {
        return $this->isOption;
    }
    
}
