<?php

// load composer stuff:
require_once __DIR__ . '/vendor/autoload.php';

// load project classes:
require_once __DIR__ . '/IZY_autoload.php';

// load config:
require_once __DIR__ . '/conf/config.php';

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$logger = new Logger('IZY_crawler');
$logger->pushHandler(new StreamHandler('log/IZY_crawler.log', Logger::DEBUG));

// main

use IZY\IZY_crawler;

$crawler = new IZY_crawler($config, $logger);

$crawler->main('http://127.0.0.1/');

