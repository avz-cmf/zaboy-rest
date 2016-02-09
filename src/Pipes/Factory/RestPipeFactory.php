<?php
/**
 * Zaboy lib (http://zaboy.org/lib/)
 * 
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Pipes\Factory;

//use Zend\ServiceManager\Factory\FactoryInterface; 
//uncomment it ^^ for Zend\ServiceManager V3
use Zend\ServiceManager\FactoryInterface; 
//comment it ^^ for Zend\ServiceManager V3
use Zend\ServiceManager\ServiceLocatorInterface;
use zaboy\rest\RestException;
use Interop\Container\ContainerInterface;
use zaboy\rest\Middleware;
use zaboy\rest\Pipe\RestPipe;
use zaboy\res\DataStore\DbTable;

/**
 * 
 * @category   Rest
 * @package    Rest
 */
class RestPipeFactory  implements FactoryInterface
{
    /**
     * Create and return an instance of the PipeMiddleware for Rest.
     *<br>
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
     * zaboy\res\TableGateway\Factory\TableGatewayAbstractFactory <br>
     * zaboy\res\DataStores\Factory\DbTableStoresAbstractFactory <br>
     * zaboy\res\Middlewares\Factory\MiddlewareStoreAbstractFactory <br>
     * to config<br>
     * 
     * @param  Interop\Container\ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return MiddlewareInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) 
    {
        $resourceName = $requestedName;    
        if (!$container->has($resourceName)) {
            throw new RestException(
                    'Can\'t make RestActionPipe' 
                    . ' for resource: ' . $resourceName
            );             
        }  
        
        $resourceObject = $container->get($resourceName);
        
        switch (true) {
            case is_a($resourceObject, 'Zend\Db\TableGateway\TableGateway'):
                $tableGateway = $resourceObject;
                $resourceObject = new DbTable($tableGateway);
            case is_a($resourceObject, 'zaboy\res\DataStores\DataStoresAbstract'):
                $dataStore = $resourceObject;
                $resourceObject = new Middleware\StoreMiddleware($dataStore);
            case $resourceObject instanceof \Zend\Stratigility\MiddlewareInterface:
                $storeMiddleware = $resourceObject;
            default:
                if (!$storeMiddleware) {
                    throw new RestException(
                            'Can\'t make RestActionPipe' 
                            . ' for resource: ' . $resourceName
                    );             
                }  
        }

        $middlewares[] = new Middleware\RequestDecoder();  
        $middlewares[] = new Middleware\RqlParser();        
        $middlewares[] = $storeMiddleware;
        $middlewares[] = new Middleware\ResponseEncoder();   
      //$middlewares[] = new Middleware\$errorHandler();   
        return new RestPipe($middlewares);
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
}    