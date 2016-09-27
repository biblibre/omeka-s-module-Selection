<?php

$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->addPsr4('BasketTest\\', __DIR__ . '/BasketTest/');

use OmekaTestHelper\Bootstrap;

Bootstrap::bootstrap(__DIR__);
Bootstrap::loginAsAdmin();
Bootstrap::enableModule('Basket');
