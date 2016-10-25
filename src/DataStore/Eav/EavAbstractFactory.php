<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore\Eav;

use Interop\Container\ContainerInterface;
use zaboy\rest\DataStore\DataStoreException;
use zaboy\rest\DataStore\Interfaces\DataStoresInterface;
use Zend\Db\TableGateway\TableGateway;
use zaboy\rest\DataStore\Factory\AbstractDataStoreFactory;
use zaboy\rest\DataStore\Eav\SysEntities;
use zaboy\rest\DataStore\Eav\Entity;
use zaboy\rest\DataStore\Eav\Prop;

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
class EavAbstractFactory extends AbstractDataStoreFactory
{

    const DB_SERVICE_NAME = 'eav db';

    protected static $KEY_IN_CREATE = 0;

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        if ($this::$KEY_IN_CANCREATE || $this::$KEY_IN_CREATE) {
            return false;
        }
        //'sys_entities' or 'entity_table_name' or 'prop_table_name'
        switch (explode('_', $requestedName)[0] . '_') {
            case SysEntities::ENTITY_PREFIX :
            case SysEntities::PROP_PREFIX :
            case explode('_', SysEntities::TABLE_NAME)[0] . '_':
                return true;
            default:
                return false;
        }
    }

    /**
     * Create and return an instance of the DataStore.
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return DataStoresInterface
     * @throws DataStoreException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if ($this::$KEY_IN_CREATE) {
            throw new DataStoreException("Create will be called without pre call canCreate method");
        }
        $this::$KEY_IN_CREATE = 1;
        $db = $container->has(self::DB_SERVICE_NAME) ? $container->get(self::DB_SERVICE_NAME) : null;
        if (null !== $db) {
            //$requestedName = 'sys_entities' or 'entity_table_name' or 'prop_table_name'
            $tableGateway = new TableGateway($requestedName, $db);
        } else {
            $this::$KEY_IN_CREATE = 0;
            throw new DataStoreException(
            'Can\'t create Zend\Db\TableGateway\TableGateway for ' . $requestedName
            );
        }

        $this::$KEY_IN_CREATE = 0;
        //'sys_entities' or 'entity_table_name' or 'prop_table_name'
        switch (explode('_', $requestedName)[0] . '_') {
            case SysEntities::ENTITY_PREFIX :
                return new Entity($tableGateway);
            case SysEntities::PROP_PREFIX :
                return new Prop($tableGateway);
            case explode('_', SysEntities::TABLE_NAME)[0] . '_':
                return new SysEntities($tableGateway);
            default:
                throw new DataStoreException(
                'Can\'t create service for ' . $requestedName
                );
        }
    }

}
