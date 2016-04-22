<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Pipe\Factory;

//use Zend\ServiceManager\Factory\FactoryInterface;
//uncomment it ^^ for Zend\ServiceManager V3
use Zend\ServiceManager\FactoryInterface;
//comment it ^^ for Zend\ServiceManager V3
use Zend\ServiceManager\ServiceLocatorInterface;
use zaboy\rest\RestException;
use Interop\Container\ContainerInterface;
use zaboy\rest\Middleware;
use zaboy\rest\Middleware\Factory\StoreMiddlewareDirectFactory;
use zaboy\rest\Pipe\RestPipe;

/**
 *
 * @category   Rest
 * @package    Rest
 */
class RestPipeFactory implements FactoryInterface
{
    /*
     * var $middlewares array
     */

    protected $middlewares;

    /**
     *
     * @param array $addMiddlewares  [10 => 'firstMiddleWare', 350 => afterRqlParser /* object * / ]
     */
    public function __construct($addMiddlewares = [])
    {
        $this->middlewares = $addMiddlewares;
    }

    /**
     * Create and return an instance of the PipeMiddleware for Rest.
     * <br>
     * If StoreMiddleware with same name as name of resource is discribed in config
     * in key 'middleware' - it will use
     * <br>
     * If DataStore with same name as name of resource is discribed in config
     * in key 'dataStore' - it will use for create StoreMiddleware
     * <br>
     * If table in DB with same name as name of resource is exist
     *  - it will use for create TableGateway for create DataStore for create StoreMiddleware
     * <br>
     * Add <br>
     * zaboy\rest\TableGateway\Factory\TableGatewayAbstractFactory <br>
     * zaboy\rest\DataStore\Factory\DbTableStoresAbstractFactory <br>
     * zaboy\rest\Middleware\Factory\MiddlewareStoreAbstractFactory <br>
     * to config<br>
     *
     * @param  Interop\Container\ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return MiddlewareInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $storeMiddlewareLazy = function (
                $request,
                $response,
                $next = null
                ) use ($container) {
            $resourceName = $request->getAttribute('Resource-Name');
            $StoreMiddlewareDirectFactory = new StoreMiddlewareDirectFactory();
            $storeMiddleware = $StoreMiddlewareDirectFactory($container, $resourceName);
            return $storeMiddleware($request, $response, $next);
        };


        $this->middlewares[100] = new Middleware\ResourceResolver();
        $this->middlewares[200] = new Middleware\RequestDecoder();
        $this->middlewares[300] = new Middleware\RqlParser();
        $this->middlewares[400] = $storeMiddlewareLazy;
        $this->middlewares[500] = new Middleware\ResponseEncoder();
        //$middlewares[600] = new Middleware\$errorHandler();

        ksort($this->middlewares);
        return new RestPipe($this->middlewares);
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        throw new RestException(
        'Don\'t use it as factory in config. ' . PHP_EOL
        . 'Call __invoke directly with resource name as parameter'
        );
    }

    public function getMiddlewares()
    {
        return $this->middlewares;
    }

}
