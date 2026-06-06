<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env.testing if exists, otherwise .env
$envFile = __DIR__ . '/../.env.testing';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/../.env';
}
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', basename($envFile));
$dotenv->safeLoad();

// Set testing constant
define('PHPUNIT_RUNNING', true);
