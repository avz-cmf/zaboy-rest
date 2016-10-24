<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\install\DataStore\Eav;

use Zend\Db\Adapter\AdapterInterface;
use zaboy\Installer as ZaboyInstaller;
use zaboy\rest\TableGateway\TableManagerMysql as TableManager;
use zaboy\async\Promise\Store as PromiseStore;
use zaboy\rest\DataStore\Eav\SysEntities;
use zaboy\rest\DataStore\Eav\Example\StoreCatalog;

/**
 * Installer class
 *
 * @category   Zaboy
 * @package    zaboy
 */
class Installer
{

    /**
     *
     * @var AdapterInterface
     */
    private $dbAdapter;

    public function __construct(AdapterInterface $dbAdapter = null)
    {
//        //set $this->entityDbAdapter as $cotainer->get('entityDbAdapter');
//        InsideConstruct::initServices();
        $this->dbAdapter = $dbAdapter;
    }

    public function uninstall()
    {
        if (getenv('APP_ENV') !== 'dev') {
            echo 'getenv("APP_ENV") !== "dev" It has did nothing';
            exit;
        }

        $tableManager = new TableManager($this->dbAdapter);
        $tableManager->deleteTable(StoreCatalog::PROP_LINKED_URL_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::PRODUCT_TABLE_NAME);
        $tableManager->deleteTable(SysEntities::TABLE_NAME);
    }

    public function install()
    {
        $tablesConfigProdaction = [
            TableManager::KEY_TABLES_CONFIGS => $this->getTableConfigProdaction()
        ];
        if (getenv('APP_ENV') === 'dev') {
            //develop only
            $tablesConfigDevelop = [
                TableManager::KEY_TABLES_CONFIGS => array_merge(
                        $this->getTableConfigProdaction(), $this->getTableConfigDevelop()
                )
            ];

            $tableManager = new TableManager($this->dbAdapter, $tablesConfigDevelop);
            $tableName = SysEntities::TABLE_NAME;

            $tableManager->rewriteTable($tableName, $tableName);
            $tableName = StoreCatalog::PRODUCT_TABLE_NAME;
            $tableManager->rewriteTable($tableName, $tableName);


            $tableManager->rewriteTable($tableName, $tableName);
            $tableName = StoreCatalog::PROP_LINKED_URL_TABLE_NAME;
            $tableManager->rewriteTable($tableName, $tableName);
        } else {
            $tableManager = new TableManager($this->dbAdapter, $tableManagerProdaction);
            $tableName = SysEntities::TABLE_NAME;
            $tableManager->rewriteTable($tableName, $tableName);
        }
    }

    protected function getTableConfigProdaction()
    {
        return [
            SysEntities::TABLE_NAME => [
                'id' => [
                    TableManager::FIELD_TYPE => 'Integer',
                    TableManager::FIELD_PARAMS => [
                        'options' => ['autoincrement' => true]
                    ]
                ],
                'entity_type' => [
                    TableManager::FIELD_TYPE => 'Varchar',
                    TableManager::FIELD_PARAMS => [
                        'length' => 100,
                        'nullable' => false,
                    ],
                ],
                'add_date' => [
                    TableManager::FIELD_TYPE => 'Timestamp',
                ]
            ]
        ];
    }

    protected function getTableConfigDevelop()
    {
        $storeCatalog = new StoreCatalog();
        return $storeCatalog->develop_tables_config;
    }

}
