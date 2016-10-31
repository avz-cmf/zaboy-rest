<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.10.16
 * Time: 1:12 PM
 */

namespace zaboy\rest\install\DataStore\Composite\Example;


use zaboy\rest\DataStore\Composite\Example\Store as CompositeStoreExample;
use zaboy\rest\DataStore\DbTable;
use zaboy\rest\install\InstallerAbstract;
use zaboy\rest\TableGateway\DbSql\MultiInsertSql;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

class Store extends InstallerAbstract
{
    /**
     *
     * @var AdapterInterface
     */
    private $dbAdapter;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->dbAdapter = $this->container->get('db');
    }

    public function addData()
    {
        $data = array_merge(
            CompositeStoreExample::$product
            , CompositeStoreExample::$images
            , CompositeStoreExample::$category
            , CompositeStoreExample::$categoryProduct
        );

        foreach ($data as $key => $value) {
            $sql = new MultiInsertSql($this->dbAdapter, $key);
            $tableGateway = new TableGateway($key, $this->dbAdapter, null, null, $sql);
            $dataStore = new DbTable($tableGateway);
            $dataStore->create($value, true);
        }
    }
}