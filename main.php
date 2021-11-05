<?php
use Symfony\Component\Console\Application;

$_LOADER = require __DIR__ . "/vendor/autoload.php";

$app = new Application("TASoft", "1.0.0");

// Add commands to the app

$app->run();