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
* Help Output needs Text
* Template System
* Logging
* Proper Error and Eception Handling
* Piped Input

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

### Custom not Found Handler

If a user calls the application without specifying a valid command line option, the notFound handler is called.
You can override the default handler, by calling the notFound method with a user function:

    $app = new Cling();
    
    $app->notFound(function() use ($app) {
        echo "I can't help you...\n";
    });
    
    
The application will `exit` immediatle if the notFound method is being called.


