<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Debug\ErrorHandler;

//use Doctrine\ORM\Tools\Setup;
//use Doctrine\ORM\EntityManager;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');
/**
 * 
 * @const string
 * Directory separator
 */
define('DS', DIRECTORY_SEPARATOR);
/**
 * Where to store the json file or the redis database
 */
define('STORAGE', dirname(__FILE__) . '/../storage');

// Coinbase
define('CRYPTO', getenv('CRYPTO') !== null && !empty(getenv('CRYPTO')) ? getenv('CRYPTO') : 'BTC');
define('CURRENCY', getenv('CURRENCY') !== null && !empty(getenv('CURRENCY')) ? getenv('CURRENCY') : 'EUR');

define('COINBASE_KEY', getenv('COINBASE_KEY') !== null && !empty(getenv('COINBASE_KEY')) ? getenv('COINBASE_KEY') : '');
define('COINBASE_SECRET', getenv('COINBASE_SECRET') !== null && !empty(getenv('COINBASE_SECRET')) ? getenv('COINBASE_SECRET') : '');
define('PAYMENT_METHOD_NAME', getenv('PAYMENT_METHOD_NAME') !== null && !empty(getenv('PAYMENT_METHOD_NAME')) ? getenv('PAYMENT_METHOD_NAME') : '');


// Redis
define('REDIS_SERVER', getenv('REDIS_SERVER') !== null && !empty(getenv('REDIS_SERVER')) ? getenv('REDIS_SERVER') : false);
define('REDIS_PORT', getenv('REDIS_PORT') !== null && !empty(getenv('REDIS_PORT')) ? getenv('REDIS_PORT') : 0);
define('REDIS_DB', getenv('REDIS_DB') !== null && !empty(getenv('REDIS_DB')) ? getenv('REDIS_DB') : '');
define('REDIS_PASS', getenv('REDIS_PASS') !== null && !empty(getenv('REDIS_PASS')) ? getenv('REDIS_PASS') : '');


define('ROCKETCHAT_REPORTING', getenv('ROCKETCHAT_REPORTING') !== null &&!empty(getenv('ROCKETCHAT_REPORTING')) ? getenv('ROCKETCHAT_REPORTING') : false);

define('SLEEPTIME', getenv('SLEEPTIME') !== null &&!empty(getenv('SLEEPTIME')) ? getenv('SLEEPTIME') : 10);



if (getenv("DEBUG")) {
    Debug::enable();
    ErrorHandler::register();
}