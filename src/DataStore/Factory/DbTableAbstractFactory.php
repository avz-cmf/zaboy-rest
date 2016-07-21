<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore\Factory;

use Interop\Container\ContainerInterface;
use zaboy\rest\DataStore\DataStoreException;
use zaboy\rest\DataStore\Interfaces\DataStoresInterface;
use Zend\Db\TableGateway\TableGateway;

/**
 * Create and return an instance of the DataStore which based on DbTable
 *
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 *    'db' => [
 *        'driver' => 'Pdo_Mysql',
 *        'host' => 'localhost',
 *        'database' => '',
 *    ]
 * 'DataStore' => [
 *
 *     'DbTable' => [
 *         'class' => 'mydatabase',
 *         'tableName' => 'mytableName',
 *         'dbAdapter' => 'db' // Service Name. 'db' by default
 *     ]
 * ]
 * </code>
 *
 * @uses zend-db
 * @see https://github.com/zendframework/zend-db
 * @category   rest
 * @package    zaboy
 */
class DbTableAbstractFactory extends AbstractDataStoreFactory
{

    const KEY_TABLE_NAME = 'tableName';

    const KEY_TABLE_GATEWAY = 'tableGateway';

    const KEY_DB_ADAPTER = 'dbAdapter';

    static $KEY_DATASTORE_CLASS = 'zaboy\rest\DataStore\DbTable';

    /**
     * Create and return an instance of the DataStore.
     *
     * 'use Zend\ServiceManager\AbstractFactoryInterface;' for V2 to
     * 'use Zend\ServiceManager\Factory\AbstractFactoryInterface;' for V3
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return DataStoresInterface
     * @throws DataStoreException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config[self::KEY_DATASTORE][$requestedName];
        $requestedClassName = $serviceConfig[self::KEY_CLASS];

        if (isset($serviceConfig[self::KEY_TABLE_GATEWAY])) {
            if($container->has($serviceConfig[self::KEY_TABLE_GATEWAY])){
                $tableGateway = $container->get($serviceConfig[self::KEY_TABLE_GATEWAY]);
            }else{
                throw new DataStoreException(
                    'Can\'t create ' . $serviceConfig[self::KEY_TABLE_GATEWAY]
                );
            }
        }else if (isset($serviceConfig[self::KEY_TABLE_NAME])) {
            $tableName = $serviceConfig[self::KEY_TABLE_NAME];

            $dbServiceName = isset($serviceConfig[self::KEY_DB_ADAPTER]) ? $serviceConfig[self::KEY_DB_ADAPTER] : 'db';
            $db = $container->has($dbServiceName) ? $container->get($dbServiceName) : null;

            if (null !== $db) {
                $tableGateway = new TableGateway($tableName, $db);
            } else {
                throw new DataStoreException(
                    'Can\'t create Zend\Db\TableGateway\TableGateway for ' . $tableName
                );
            }
        } else {
            throw new DataStoreException(
                'There is not table name for ' . $requestedName . 'in config \'dataStore\''
            );
        }


        return new $requestedClassName($tableGateway);
    }
}
