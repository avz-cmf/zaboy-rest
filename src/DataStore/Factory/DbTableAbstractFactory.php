<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore\Factory;

use zaboy\rest\AbstractFactoryAbstract;
use Zend\Db\TableGateway\TableGateway;
use Interop\Container\ContainerInterface;

/**
 * Create and return an instance of the DataStore which based on DbTable
 *
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 * 	'db' => [
 * 		'driver' => 'Pdo_Mysql',
 * 		'host' => 'localhost',
 * 		'database' => '',
 * 	]
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
class DbTableAbstractFactory extends AbstractFactoryAbstract
{

    /**
     * Can the factory create an instance for the service?
     *
     * For Service manager V3
     * Edit 'use' section if need:
     * Change:
     * 'use Zend\ServiceManager\AbstractFactoryInterface;' for V2 to
     * 'use Zend\ServiceManager\Factory\AbstractFactoryInterface;' for V3
     *
     * @param  Interop\Container\ContainerInterface $container
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
        return is_a($requestedClassName, 'zaboy\rest\DataStore\DbTable', true);
    }

    /**
     * Create and return an instance of the DataStore.
     *
     * 'use Zend\ServiceManager\AbstractFactoryInterface;' for V2 to
     * 'use Zend\ServiceManager\Factory\AbstractFactoryInterface;' for V3
     *
     * @param  Interop\Container\ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return \DataStores\Interfaces\DataStoresInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config['dataStore'][$requestedName];
        $requestedClassName = $serviceConfig['class'];
        if (isset($serviceConfig['tableName'])) {
            $tableName = $serviceConfig['tableName'];
        } else {
            throw new DataStoreException(
            'There is not table name for ' . $requestedName . 'in config \'dataStore\''
            );
        }
        $dbServiceName = isset($serviceConfig['dbAdapter']) ? $serviceConfig['dbAdapter'] : 'db';
        $db = $container->has($dbServiceName) ? $container->get($dbServiceName) : null;
        if (null !== $db) {
            $tableGateway = new TableGateway($tableName, $db);
        } else {
            throw new DataStoreException(
            'Can\'t create Zend\Db\TableGateway\TableGateway for ' . $tableName
            );
        }
        return new $requestedClassName($tableGateway);
    }

}
