<?php
// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));

// Setup autoloading
require '/vendor/autoload.php';

use Zend\Stratigility\MiddlewarePipe;
use Zend\Diactoros\Server;
use zaboy\middleware\Middleware;
use zaboy\middleware\Middlewares\Factory\RestActionPipeFactory;


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
        $restActionPipeFactory = new RestActionPipeFactory();
        $restActionPipe = $restActionPipeFactory($container, $resourceName);
        return $restActionPipe($request, $response, $next);
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
