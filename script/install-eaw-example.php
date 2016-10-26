<?php

error_reporting(E_ALL);
// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
$container = include 'config/container.php';

use zaboy\rest\install\Installer;

$installer = new Installer($container);
$installer->addDataEavExampleStoreCatalog();


