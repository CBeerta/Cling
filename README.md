# Cling

Cling is a Micro Framework for building Command Line Applications

# Features

* Routing of Command Line Arguments
* Short and Long Arguments
* Automagic `--help` creation
* Configuration

# Todo

As Cling is **very** new, there's still lot of work left to be done

* Testing
* Template System
* Logging
* Proper Error and Eception Handling
* The :stdin command needs to check if there is data coming in on <STDIN>

# Requirements

* PHP 5.3

# "Hello World" Application

    <?php
    require 'Cling/Cling.php';

    $app = new Cling();
    $app->command('hello-world:', function($name) {
        echo "Hello $name\n";
    })->help("Hello World Example Command.");

    $app->run();


# Documentation

## Configure Cling

To configue Cling, you can pass an associative array to the constructor method. The Options are:

* **debug** Enable or Disable debug output (Default: false)

## Routes

### Command Line Options

A Command Line Option can be handled by the `command` function:

    $app->command('<Long Option Name>', '<1 letter short option'>, '<callable>');
    
The Short Option parameter is optional.

### Special Routes

A `:*` command is a Route that is executed regardless of Command Line Options. Multiple `:*` Routes are executed in the order they are set.

### Custom not Found Handler

If a user calls the application without specifying a valid command line option, the notFound handler is called.
You can override the default handler, by calling the notFound method with a user function:

    $app = new Cling();
    
    $app->notFound(function() use ($app) {
        echo "I can't help you...\n";
    });
    
The application will `exit` immediatle if the notFound method is being called.

## Views

Console Applications should, just like a Website, facilitate Views:

    $app = new Cling(array('template.path' => 'views/'));
    
    $app->command('hello-world:', function($name) use ($app) {
        $app->view->set('name', $name);
        $app->view->display('example.txt.php')
    })->help("Example with a View.");
    
The view would look then something like:
    
    Hello <?php echo $name; ?>!
    
    I Hope You're doing fine.

## Logging

To Log message to files, stdout or stderr:

    $app = new Cling(array('log.destination' => \Cling\Logger::LOG_STDOUT));
    
    $app->logger->logInfo('Hello World!');
    
### Log Destinations

* \Cling\Logger::LOG_STDOUT    => Log to STDOUT
* \Cling\Logger::LOG_STDERR    => Log to STDERR
* \Cling\Logger::LOG_FILE      => Log to a file in a directory set by 'log.dir'
* \Cling\Logger::LOG_ERROR_LOG => Log via Default 'error_log'

### Log Levels

The Following Log Levels Are available

* logEmerg   => Emergency: system is unusable
* logAlert   => Alert: action must be taken immediately
* logCrit    => Critical: critical conditions
* logError   => Error: error conditions
* logWarn    => Warning: warning conditions
* logNotice  => Notice: normal but significant condition
* logInfo    => Informational: informational messages
* logDebug   => Debug: debug messages



