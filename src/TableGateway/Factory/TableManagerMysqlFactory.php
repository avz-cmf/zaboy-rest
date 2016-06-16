<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\TableGateway\Factory;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Metadata\Metadata;
use zaboy\rest\RestException;
use Interop\Container\ContainerInterface;
use zaboy\rest\FactoryAbstract;
use zaboy\rest\TableGateway\TableManagerMysql;

/**
 * Create and return an instance of the TableManagerMysql
 *
 * Return TableManagerMysql
 *
 * Requre service with name 'db' - db adapter
 *
 * @uses zend-db
 * @see https://github.com/zendframework/zend-db
 * @category   rest
 * @package    zaboy
 */
class TableManagerMysqlFactory extends FactoryAbstract
{

    /**
     * Create and return an instance of the TableGateway.
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
        $db = $container->get('db');
        if (!isset($config[TableManagerMysql::KEY_IN_CONFIG])) {
            //throw new RestException('There is not "tableManager" key in config');
            $tableManagerConfig = [];
        } else {
            $tableManagerConfig = $config[TableManagerMysql::KEY_IN_CONFIG];
        }

        $tableManager = new TableManagerMysql($db, $tableManagerConfig);
        return $tableManager;
    }

}
