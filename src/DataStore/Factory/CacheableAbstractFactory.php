<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.07.16
 * Time: 11:51
 */

namespace zaboy\rest\DataStore\Factory;

use Interop\Container\ContainerInterface;
use zaboy\rest\AbstractFactoryAbstract;
use zaboy\rest\DataStore\Cacheable;
use zaboy\rest\DataStore\DataStoreException;

class CacheableAbstractFactory extends AbstractFactoryAbstract
{

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed
     * @throws DataStoreException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config['dataStore'][$requestedName];
        $requestedClassName = $serviceConfig['class'];
        if (isset($serviceConfig['dataSource'])) {
            if ($container->has($serviceConfig['dataSource'])) {
                $getAll = $container->get($serviceConfig['dataSource']);
            } else {
                throw new DataStoreException(
                    'There is DataSource not created ' . $requestedName . 'in config \'dataStore\''
                );
            }
        } else {
            throw new DataStoreException(
                'There is DataSource for ' . $requestedName . 'in config \'dataStore\''
            );
        }
        if (isset($serviceConfig['cacheable'])) {
            if ($container->has($serviceConfig['cacheable'])) {
                $cashStore = $container->get($serviceConfig['cacheable']);
            } else {
                throw new DataStoreException(
                    'There is DataSource for ' . $serviceConfig['cacheable'] . 'in config \'dataStore\''
                );
            }
        } else {
            $cashStore = null;
        }

        //$cashStore = isset($serviceConfig['cashStore']) ?  new $serviceConfig['cashStore']() : null;
        return new $requestedClassName($getAll, $cashStore);
    }

    /**
     * Can the factory create an instance for the service?
     *
     * For Service manager V3
     * Edit 'use' section if need:
     * Change:
     * 'use Zend\ServiceManager\AbstractFactoryInterface;' for V2 to
     * 'use Zend\ServiceManager\Factory\AbstractFactoryInterface;' for V3
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');
        if (!isset($config['dataStore'][$requestedName]['class'])) {
            return false;
        }
        $requestedClassName = $config['dataStore'][$requestedName]['class'];

        $result = is_a("$requestedClassName", 'zaboy\rest\DataStore\Cacheable', true);

        return $result;
    }
}
