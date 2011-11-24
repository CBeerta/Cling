<?php
/**
* Little Example Application to Demo Cling
**/

require_once 'Cling.php';

$app = new Cling();

/**
* Command Line
**/
$app->command('help', 'h',
    function() use ($app) {
        echo $app;
        exit;
    })
    ->help("This Helptext.");

/**
* Hellow World long option with a parameter
**/
$app->command('hello-world:', 
    function($name) {
        echo "Hello $name\n";
    })
    ->help("Hello World Example.");


/**
* Execute The App
**/
$app->run();

