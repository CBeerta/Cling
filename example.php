#!/usr/bin/env php
<?php
/**
* Little Example Application to Demo Cling
**/

require_once 'Cling/Cling.php';

$app = new \Cling\Cling(
    array(
        'debug' => true,
        'log.destination' => \Cling\Logger::LOG_STDERR,
        'log.dir' => __DIR__
    )
);

/**
* Help Text
*
* If no other command should be executed, use "exit" in your code
**/
$app->command('help', 'h',
    function() use ($app) {
        echo $app->notFound();
        exit;
    })
    ->help("This Helptext.");

/**
* Hello World long option with a parameter
**/
$app->command('hello-world:', 
    function($name) {
        echo "Hello $name\n";
    })
    ->help("Hello World Example.");

/**
* A Route that is executed without a command line option
**/
$app->command(':*', 
    function() use ($app) {
        $app->logger->logNotice('Hello World Logging');
        echo "Always Executed.\n";
    });

/**
* A route that captures piped data from stdin
**/
/*
$app->command(':stdin', 
    function() use ($app) {
        while (($data = $app->route()->readStdin()) !== false) {
            print_r($data);
        }
    });
*/

/**
* Execute The App
**/
$app->run();

