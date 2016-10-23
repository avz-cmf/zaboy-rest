<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore\Factory;

use Interop\Container\ContainerInterface;
use zaboy\rest\AbstractFactoryAbstract;
use zaboy\rest\DataStore\DataStoreException;

abstract class AbstractDataStoreFactory extends AbstractFactoryAbstract
{

    const KEY_DATASTORE = 'dataStore';

    protected static $KEY_DATASTORE_CLASS = 'zaboy\rest\DataStore\DataStoreAbstract';
    protected static $KEY_IN_CANCREATE = 0;
    protected static $KEY_IN_CREATE = 0;

    /**
     * Can the factory create an instance for the service?
     * Use protection against circular dependencies (via static flags).
     * read https://github.com/avz-cmf/zaboy-rest/tree/master/src/DataStore/Factory/README.md
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
        if ($this::$KEY_IN_CANCREATE || $this::$KEY_IN_CREATE) {
            return false;
        }
        $this::$KEY_IN_CANCREATE = 1;
        $config = $container->get('config');
        if (!isset($config[self::KEY_DATASTORE][$requestedName][self::KEY_CLASS])) {
            $result = false;
        } else {
            $requestedClassName = $config[self::KEY_DATASTORE][$requestedName][self::KEY_CLASS];
            $result = is_a($requestedClassName, $this::$KEY_DATASTORE_CLASS, true);
        }
        $this::$KEY_IN_CANCREATE = 0;
        return $result;
    }

}
