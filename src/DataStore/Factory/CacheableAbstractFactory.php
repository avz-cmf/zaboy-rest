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

class CacheableAbstractFactory extends AbstractDataStoreFactory
{
    static $KEY_DATASTORE_CLASS = 'zaboy\rest\DataStore\Cacheable';

    const KEY_DATASOURCE = 'dataSource';

    const KEY_CACHEABLE = 'cacheable';

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
        $serviceConfig = $config[self::KEY_DATASTORE][$requestedName];
        $requestedClassName = $serviceConfig[self::KEY_CLASS];
        if (isset($serviceConfig[self::KEY_DATASOURCE])) {
            if ($container->has($serviceConfig[self::KEY_DATASOURCE])) {
                $getAll = $container->get($serviceConfig[self::KEY_DATASOURCE]);
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
        if (isset($serviceConfig[self::KEY_CACHEABLE])) {
            if ($container->has($serviceConfig[self::KEY_CACHEABLE])) {
                $cashStore = $container->get($serviceConfig[self::KEY_CACHEABLE]);
            } else {
                throw new DataStoreException(
                    'There is DataSource for ' . $serviceConfig[self::KEY_CACHEABLE] . 'in config \'dataStore\''
                );
            }
        } else {
            $cashStore = null;
        }

        //$cashStore = isset($serviceConfig['cashStore']) ?  new $serviceConfig['cashStore']() : null;
        return new $requestedClassName($getAll, $cashStore);
    }
}
