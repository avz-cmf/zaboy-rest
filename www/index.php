<?php
// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));

// Setup autoloading
require '/vendor/autoload.php';

use Zend\Stratigility\MiddlewarePipe;
use Zend\Diactoros\Server;
use zaboy\rest\Middleware;
use zaboy\rest\Middlewares\Factory\RestPipeFactory;

$container = include 'config/container.php';

$app = new MiddlewarePipe();

$rest =  new MiddlewarePipe();
//set Attribute 'Resource-Name'
//It can do router if you use zend-expressive
$rest->pipe('/',  new Middleware\Rest\ResourceResolver());
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
