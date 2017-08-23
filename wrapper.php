<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/IZY_crawler.php';
require_once __DIR__ . '/conf/config.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$logger = new Logger('IZY_crawler');
$logger->pushHandler(new StreamHandler('log/IZY_crawler.log', Logger::DEBUG));


$crawler = new IZY_crawler($config, $logger);

$crawler->main('http://127.0.0.1/');

