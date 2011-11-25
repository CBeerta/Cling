<?php
/**
* Little Example Application to Demo Cling
**/

require_once 'Cling.php';

$app = new Cling(array('debug' => true));

/**
* Help Text
*
* If no other command should be executed, use "exit" in your code
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

