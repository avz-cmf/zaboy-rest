<?php

// try http://__zaboy-rest/api/rest/index_StoreMiddleware?fNumberOfHours=8&fWeekday=Monday
// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));
//test_res_http
// Setup autoloading
require '/vendor/autoload.php';

use Zend\Stratigility\MiddlewarePipe;
use Zend\Diactoros\Server;
use zaboy\rest\Pipe\Factory\RestPipeFactory;

$container = include 'config/container.php';
$tableName = 'test_res_http'; //'index_php_table';
//include 'createTable.php';

$app = new MiddlewarePipe();

$restPipeFactory = new RestPipeFactory();
$rest = $restPipeFactory($container, '');
$app->pipe('/api/rest', $rest);

$server = Server::createServer($app, $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
);
$server->listen();

//$deleteStatementStr = "DROP TABLE IF EXISTS " .  $quoteTableName;
//$deleteStatement = $adapter->query($deleteStatementStr);
//$deleteStatement->execute();
