<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\install\DataStore\Eav;

use Zend\Db\Adapter\AdapterInterface;
use zaboy\rest\TableGateway\TableManagerMysql as TableManager;
use zaboy\rest\DataStore\Eav\SysEntities;
use zaboy\rest\DataStore\Eav\Example\StoreCatalog;
use zaboy\rest\install\InstallerAbstract;
use zaboy\rest\DataStore\Eav\EavAbstractFactory;

/**
 * Installer class
 *
 * @category   Zaboy
 * @package    zaboy
 */
class Installer extends InstallerAbstract
{

    /**
     *
     * @var AdapterInterface
     */
    private $dbAdapter;

    /**
     *
     *
     * Add to config:
     * <code>
     *    'services' => [
     *        'aliases' => [
     *            EavAbstractFactory::DB_SERVICE_NAME => getenv('APP_ENV') === 'prod' ? 'dbOnProduction' : 'local-db',
     *        ],
     *        'abstract_factories' => [
     *            EavAbstractFactory::class,
     *        ]
     *    ],
     * </code>
     * @param type $container
     */
    public function __construct($container)
    {
        parent::__construct($container);
        $this->dbAdapter = $this->container->get(EavAbstractFactory::DB_SERVICE_NAME);
    }

    public function uninstall()
    {
        if (getenv('APP_ENV') !== 'dev') {
            echo 'getenv("APP_ENV") !== "dev" It has did nothing';
            exit;
        }

        $tableManager = new TableManager($this->dbAdapter);
        $tableManager->deleteTable(StoreCatalog::PROP_LINKED_URL_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::PROP_PRODUCT_CATEGORY_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::PROP_TAG_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::PRODUCT_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::CATEGORY_TABLE_NAME);
        $tableManager->deleteTable(StoreCatalog::TAG_TABLE_NAME);
        $tableManager->deleteTable(SysEntities::TABLE_NAME);
    }

    public function install()
    {
        if (getenv('APP_ENV') === 'dev') {
            //develop only
            $tablesConfigDevelop = [
                TableManager::KEY_TABLES_CONFIGS => array_merge(
                        SysEntities::getTableConfigProdaction(), StoreCatalog::$develop_tables_config
                )
            ];
            $tableManager = new TableManager($this->dbAdapter, $tablesConfigDevelop);

            $tableManager->rewriteTable(SysEntities::TABLE_NAME);
            $tableManager->rewriteTable(StoreCatalog::PRODUCT_TABLE_NAME);
            $tableManager->rewriteTable(StoreCatalog::TAG_TABLE_NAME);
            $tableManager->rewriteTable(StoreCatalog::CATEGORY_TABLE_NAME);
            $tableManager->rewriteTable(StoreCatalog::PROP_LINKED_URL_TABLE_NAME);
            $tableManager->rewriteTable(StoreCatalog::PROP_PRODUCT_CATEGORY_TABLE_NAME);
            $tableManager->rewriteTable(StoreCatalog::PROP_TAG_TABLE_NAME);
        } else {
            $tablesConfigProdaction = [
                TableManager::KEY_TABLES_CONFIGS => SysEntities::getTableConfigProdaction()
            ];
            $tableManager = new TableManager($this->dbAdapter, $tablesConfigProdaction);

            $tableManager->createTable(SysEntities::TABLE_NAME);
        }
    }

}
