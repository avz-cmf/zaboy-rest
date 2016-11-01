<?php

error_reporting(E_ALL);
// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

// Define application environment - 'dev' or 'prop'
if (getenv('APP_ENV') === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $env = 'develop';
}

$container = include 'config/container.php';

use zaboy\rest\install\Installer;

$installer = new Installer($container);
$installer->addDataEavExampleStoreCatalog();


