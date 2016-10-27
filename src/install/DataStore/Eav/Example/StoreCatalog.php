<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\install\DataStore\Eav\Example;

use zaboy\rest\DataStore\DbTable;
use zaboy\rest\DataStore\Eav\EavAbstractFactory;
use zaboy\rest\DataStore\Eav\Example\StoreCatalog as EavExampleStoreCatalog;
use zaboy\rest\install\InstallerAbstract;
use zaboy\rest\TableGateway\DbSql\MultiInsertSql;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

/**
 * Installer class
 *
 * @category   Zaboy
 * @package    zaboy
 */
class StoreCatalog extends InstallerAbstract
{

    /**
     *
     * @var AdapterInterface
     */
    private $dbAdapter;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->dbAdapter = $this->container->get(EavAbstractFactory::DB_SERVICE_NAME);
    }

    public function addData()
    {
        $data = array_merge(
            EavExampleStoreCatalog::$sys_entities,
            EavExampleStoreCatalog::$entity_product,
            EavExampleStoreCatalog::$entity_category,
            EavExampleStoreCatalog::$entity_tag,
            EavExampleStoreCatalog::$entity_mainicon,
            EavExampleStoreCatalog::$prop_tag,
            EavExampleStoreCatalog::$prop_product_category,
            EavExampleStoreCatalog::$prop_linked_url
        );

        foreach ($data as $key => $value) {
            $sql = new MultiInsertSql($this->dbAdapter, $key);
            $tableGateway = new TableGateway($key, $this->dbAdapter, null, null, $sql);
            $dataStore = new DbTable($tableGateway);
            $dataStore->create($value, true);
        }
    }

}
