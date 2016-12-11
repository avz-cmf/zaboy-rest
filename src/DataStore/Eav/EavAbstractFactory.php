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
use zaboy\rest\DataStore\Eav\SuperEntity;
use Zend\Db\Adapter\AdapterInterface;

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

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        //'SuperEtity - 'entity_table_name_1~entity_table_name_1'
        $superEtity = strpos($requestedName, SuperEntity::INNER_JOIN);
        if ($superEtity) {
            $eavDataStores = explode(SuperEntity::INNER_JOIN, $requestedName);
            foreach ($eavDataStores as $eavDataStore) {
                if (strpos($eavDataStore, SysEntities::ENTITY_PREFIX) !== 0) {
                    return false;
                }
            }
            return true;
        }

        return strpos($requestedName, SysEntities::ENTITY_PREFIX) === 0 ||
                strpos($requestedName, SysEntities::PROP_PREFIX) === 0 ||
                $requestedName == SysEntities::TABLE_NAME;
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
        $db = $container->has(static::DB_SERVICE_NAME) ? $container->get(static::DB_SERVICE_NAME) : null;
        if (null === $db) {
            throw new DataStoreException(
            'Can\'t create Zend\Db\TableGateway\TableGateway for ' . $requestedName
            );
        }

        //'SuperEtity - 'entity_table_name_1~entity_table_name_1'
        if (strpos($requestedName, SuperEntity::INNER_JOIN)) {
            $eavDataStores = explode(SuperEntity::INNER_JOIN, $requestedName);
            $eavDataStoresObjests = [];
            foreach ($eavDataStores as $eavDataStore) {
                $eavDataStoresObjests[] = $this->getEavDataStore($db, $eavDataStore);
                $eavDataStoresObjests[] = SuperEntity::INNER_JOIN;
            }
            array_pop($eavDataStoresObjests);
            $tableGateway = new TableGateway(SysEntities::TABLE_NAME, $db);
            $result = new SuperEntity($tableGateway, $eavDataStoresObjests);
            return $result;
        }
        //'sys_entities' or 'entity_table_name' or 'prop_table_name'
        return $this->getEavDataStore($db, $requestedName);
    }

    public function getEavDataStore(AdapterInterface $db, $requestedName)
    {
        //$requestedName = 'sys_entities' or 'entity_table_name' or 'prop_table_name'
        $tableGateway = new TableGateway($requestedName, $db);
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
