<?php

// try http://__zaboy-rest/api/rest/index_StoreMiddleware?fNumberOfHours=8&fWeekday=Monday
// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));

require 'vendor/autoload.php';

use Zend\Diactoros\Server;
use zaboy\rest\Pipe\MiddlewarePipeOptions;
use zaboy\rest\Pipe\Factory\RestRqlFactory;

// Define application environment - 'dev' or 'prop'
if (getenv('APP_ENV') === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $env = 'develop';
}

$container = include 'config/container.php';

$RestRqlFactory = new RestRqlFactory();
$rest = $RestRqlFactory($container, '');

$app = new MiddlewarePipeOptions(['env' => $env ? $env : null]); //['env' => 'develop']
$app->pipe('/api/rest', $rest);

$server = Server::createServer($app, $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
$server->listen();


