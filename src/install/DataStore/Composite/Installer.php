<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.10.16
 * Time: 1:16 PM
 */

namespace zaboy\rest\install\DataStore\Composite;

use zaboy\rest\DataStore\Composite\Example\Store;
use zaboy\rest\install\InstallerAbstract;
use Zend\Db\Adapter\AdapterInterface;
use zaboy\rest\TableGateway\TableManagerMysql as TableManager;

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
        $this->dbAdapter = $this->container->get('db');
    }

    public function uninstall()
    {
        if (getenv('APP_ENV') !== 'dev') {
            echo 'getenv("APP_ENV") !== "dev" It has did nothing';
            exit;
        }

        $tableManager = new TableManager($this->dbAdapter);
        $tableManager->deleteTable(Store::IMAGE_TABLE_NAME);
        $tableManager->deleteTable(Store::CATEGORY_PRODUCT_TABLE_NAME);
        $tableManager->deleteTable(Store::PRODUCT_TABLE_NAME);
        $tableManager->deleteTable(Store::CATEGORY_TABLE_NAME);

    }

    public function install()
    {
        if (getenv('APP_ENV') === 'dev') {
            //develop only
            $tablesConfigDevelop = [
                TableManager::KEY_TABLES_CONFIGS => Store::$develop_tables_config
            ];
            $tableManager = new TableManager($this->dbAdapter, $tablesConfigDevelop);
            $tableManager->rewriteTable(Store::PRODUCT_TABLE_NAME);
            $tableManager->rewriteTable(Store::IMAGE_TABLE_NAME);
            $tableManager->rewriteTable(Store::CATEGORY_TABLE_NAME);
            $tableManager->rewriteTable(Store::CATEGORY_PRODUCT_TABLE_NAME);

        }
    }
}