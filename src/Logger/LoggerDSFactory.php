<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 17.12.16
 * Time: 11:48 AM
 */

namespace zaboy\rest\Logger;


use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceManager;

class LoggerDSFactory implements FactoryInterface
{

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        if (isset($config['logger']) && $config['logger']['dataStore']) {
            $dataStore = $config['logger']['dataStore'];
            if ($container->has($dataStore)) {
                $dataStore = $container->get($dataStore);
                $logger = new LoggerDS($dataStore);
                if ($container instanceof ServiceManager) {
                    (new LoggerAwareSM($container))->setLogger($logger);
                }
            } else {
                throw  new ServiceNotCreatedException("Log DataStore not set");
            }
        } else {
            throw  new ServiceNotCreatedException("Logger Config not set");
        }
        return $logger;
    }
}