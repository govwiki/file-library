<?php

require __DIR__ . '/../vendor/autoload.php';

$app = \App\Kernel\AppFactory::create();
$app->run();
