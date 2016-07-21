<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 21.07.16
 * Time: 16:28
 */

namespace zaboy\rest\DataStore\Factory;

use Interop\Container\ContainerInterface;
use zaboy\rest\AbstractFactoryAbstract;

abstract class AbstractDataStoreFactory extends AbstractFactoryAbstract
{
    const KEY_DATASTORE = 'dataStore';

    static $KEY_DATASTORE_CLASS = 'zaboy\rest\DataStore\DataStoreAbstract';

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
        if (!isset($config[self::KEY_DATASTORE][$requestedName][self::KEY_CLASS])) {
            return false;
        }

        $requestedClassName = $config[self::KEY_DATASTORE][$requestedName][self::KEY_CLASS];
        $result = is_a($requestedClassName, $this::$KEY_DATASTORE_CLASS, true);
        return $result;
    }
}
