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


