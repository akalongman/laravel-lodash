<?php

declare(strict_types=1);

/*
 * Set error reporting to the level to which Mockery code must comply.
 */
error_reporting(-1);

/*
 * Set UTC timezone.
 */
date_default_timezone_set('UTC');

$root = realpath(__DIR__);
/**
 * Check that --dev composer installation was done
 */
if (! file_exists($root . '/vendor/autoload.php')) {
    throw new Exception(
        'Please run "php composer.phar install --dev" in root directory
        to setup unit test dependencies before running the tests',
    );
}

// Include the Composer autoloader
$loader = require __DIR__ . '/../vendor/autoload.php';

/*
 * Unset global variables that are no longer needed.
 */
unset($root, $loader);
