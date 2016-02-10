<?php

// try http://__zaboy-rest/api/rest/index_StoreMiddleware?fNumberOfHours=8&fWeekday=Monday


// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));

// Setup autoloading
require '/vendor/autoload.php';

use Zend\Stratigility\MiddlewarePipe;
use Zend\Diactoros\Server;
use  Zend\Db\Adapter\Adapter;
use zaboy\rest\Middleware;
use zaboy\rest\Pipes\Factory\RestPipeFactory;

$container = include 'config/container.php';

    $adapter = $container->get('db');
    $quoteTableName = $adapter->platform->quoteIdentifier('index_php_table');

    $deleteStatementStr = "DROP TABLE IF EXISTS " .  $quoteTableName;
    $adapter->query($deleteStatementStr, Adapter::QUERY_MODE_EXECUTE);

    $createStr = 
        "CREATE TABLE  "  .
        $quoteTableName .
        '(' .    
            ' id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ' .
            ' fWeekday CHAR(20), ' .
            ' fNumberOfHours INT ' .
        ' ) ' .    
        'ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;'
    ;    
    $adapter->query($createStr, Adapter::QUERY_MODE_EXECUTE);

    $insertStr = "INSERT INTO $quoteTableName (id, fWeekday, fNumberOfHours) VALUES (1, 'Monday', 8)";
    $adapter->query($insertStr, Adapter::QUERY_MODE_EXECUTE);
    $insertStr = "INSERT INTO $quoteTableName (id, fWeekday, fNumberOfHours) VALUES (2, 'Tuesday', 8)";
    $adapter->query($insertStr, Adapter::QUERY_MODE_EXECUTE);
    $insertStr = "INSERT INTO $quoteTableName (id, fWeekday, fNumberOfHours) VALUES (3, 'Wednesday', 8)";
    $adapter->query($insertStr, Adapter::QUERY_MODE_EXECUTE);
    $insertStr = "INSERT INTO $quoteTableName (id, fWeekday, fNumberOfHours) VALUES (4, 'Monday', 6)";
    $adapter->query($insertStr, Adapter::QUERY_MODE_EXECUTE);           
    $insertStr = "INSERT INTO $quoteTableName (id, fWeekday, fNumberOfHours) VALUES (5, 'Thursday', 6)";
    $adapter->query($insertStr, Adapter::QUERY_MODE_EXECUTE);
    $insertStr = "INSERT INTO $quoteTableName (id, fWeekday, fNumberOfHours) VALUES (6, 'Friday', 6)";
    $adapter->query($insertStr, Adapter::QUERY_MODE_EXECUTE);
    $insertStr = "INSERT INTO $quoteTableName (id, fWeekday, fNumberOfHours) VALUES (7, 'Monday', 8)";
    $adapter->query($insertStr, Adapter::QUERY_MODE_EXECUTE);
    $insertStr = "INSERT INTO $quoteTableName (id, fWeekday, fNumberOfHours) VALUES (8, 'Tuesday', 4)";
    $adapter->query($insertStr, Adapter::QUERY_MODE_EXECUTE);

    
    
$app = new MiddlewarePipe();

$rest =  new MiddlewarePipe();
//set Attribute 'Resource-Name'
//It can do router if you use zend-expressive
$rest->pipe('/',  new Middleware\ResourceResolver());
$restMiddlewareLazy = function (
    $request, 
    $response,   
    $next = null
    ) use ($container) {
        $resourceName = $request->getAttribute('Resource-Name');
        $restPipeFactory = new RestPipeFactory();
        $restPipe = $restPipeFactory($container, $resourceName);
        return $restPipe($request, $response, $next);
};
$rest->pipe('/',  $restMiddlewareLazy);
$app->pipe('/api/rest', $rest);

$server = Server::createServer($app,
  $_SERVER,
  $_GET,
  $_POST,
  $_COOKIE,
  $_FILES
);
$server->listen();

$deleteStatementStr = "DROP TABLE IF EXISTS " .  $quoteTableName;
$deleteStatement = $adapter->query($deleteStatementStr);
$deleteStatement->execute();
