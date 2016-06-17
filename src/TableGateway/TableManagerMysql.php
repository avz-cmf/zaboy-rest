<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\TableGateway;

use Zend\Db\Sql\Ddl\CreateTable;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Ddl\Constraint;
use zaboy\rest\RestException;
use Zend\Db\Sql;
use Zend\Db\Adapter;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Metadata\Source;

/**
 * Create table and get info
 *
 * Uses:
 * <code>
 *  $tableManager = new TableManagerMysql($adapter);
 *  $tableData = [
 *      'id' => [
 *          'fild_type' => 'Integer',
 *          'fild_params' => [
 *          'options' => ['autoincrement' => true]
 *          ]
 *      ],
 *      'name' => [
 *          'fild_type' => 'Varchar',
 *          'fild_params' => [
 *              'length' => 10,
 *              'nullable' => true,
 *              'default' => 'what?'
 *          ]
 *      ]
 *  ];
 *  $tableManager->createTable($tableName, $tableData);
 * </code>
 *
 * As you can see, array $tableData has 3 keys and next structure:
 * <code>
 *  $tableData = [
 *      'FildName' => [
 *          'fild_type' => 'Integer',
 *          'fild_params' => [
 *          'options' => ['autoincrement' => true]
 *          ]
 *      ],
 *      'NextFildName' => [
 *  ...
 * </code>
 *
 * About value of key <b>'fild_type'</b> - see {@link TableManagerMysql::$fildClasses}<br>
 * About value of key <b>'fild_params'</b> - see {@link TableManagerMysql::$parameters}<br>
 *
 * The <b>'options'</b> may be:
 * <ul>
 * <li>unsigned</li>
 * <li>zerofill</li>
 * <li>identity</li>
 * <li>serial</li>
 * <li>autoincrement</li>
 * <li>comment</li>
 * <li>columnformat</li>
 * <li>format</li>
 * <li>storage</li>
 * </ul>
 *
 * @see Examples/TableGateway/index.php
 * @category   rest
 * @package    zaboy
 */
class TableManagerMysql
{

    const FILD_TYPE = 'fild_type';
    const FILD_PARAMS = 'fild_params';
    const KEY_IN_CONFIG = 'tableManagerMysql';
    const KEY_TABLES_CONFIGS = 'tablesConfigs';
    const KEY_AUTOCREATE_TABLES = 'autocreateTables';

    /**
     *
     * @var Zend\Db\Adapter\Adapter
     */
    protected $db;

    /**
     *
     * @var arraay
     */
    protected $config;

    /**
     *
     * @var array
     */
    protected $fildClasses = [
        'Colum' => [ 'BigInteger', 'Boolean', 'Date', 'Datetime', 'Integer', 'Time', 'Timestamp'],
        'LengthColumn' => [ 'Binary', 'Blob', 'Char', 'Text', 'Varbinary', 'Varchar'],
        'PrecisionColumn' => [ 'Decimal', 'Float', 'Floating']
    ];

    /**
     *
     * @var array
     */
    protected $parameters = [
        'Colum' => [ 'nullable' => false, 'default' => null, 'options' => []],
        'LengthColumn' => [ 'length' => null, 'nullable' => false, 'default' => null, 'options' => []],
        'PrecisionColumn' => [ 'digits' => null, 'decimal' => null, 'nullable' => false, 'default' => null, 'options' => []]
    ];

    /**
     *
     * @param \Zend\Db\Adapter\Adapter $db
     */
    public function __construct(Adapter\Adapter $db, $config = null)
    {
        $this->db = $db;
        $this->config = $config;

        if (!isset($this->config[self::KEY_AUTOCREATE_TABLES])) {
            return;
        }
        $autocreateTables = $this->config[self::KEY_AUTOCREATE_TABLES];
        foreach ($autocreateTables as $tableName => $tableConfig) {
            if (!$this->hasTable($tableName)) {
                $this->create($tableName, $tableConfig);
            }
        }
    }

    /**
     *
     * @param string $tableName
     * @param string $tableConfig
     * @return mix
     */
    public function createTable($tableName, $tableConfig)
    {
        if ($this->hasTable($tableName)) {
            throw new DataStoreException(
            "Table with name $tableName is exist. Use rewriteTable()"
            );
        }
        return $this->create($tableName, $tableConfig);
    }

    /**
     *
     * @param string $tableName
     * @param string $tableConfig
     * @return mix
     */
    public function rewriteTable($tableName, $tableConfig)
    {
        if ($this->hasTable($tableName)) {
            $this->deleteTable($tableName);
        }
        return $this->create($this->getTableConfig($tableName, $tableConfig));
    }

    /**
     * Delete Table
     *
     * @todo use zend deleteTable
     */
    public function deleteTable($tableName)
    {
        $deleteStatementStr = "DROP TABLE IF EXISTS "
                . $this->db->platform->quoteIdentifier($tableName);
        $deleteStatement = $this->db->query($deleteStatementStr);
        return $deleteStatement->execute();
    }

    /**
     *
     *
     * @see http://framework.zend.com/manual/current/en/modules/zend.db.metadata.html
     * @param string $tableName
     * @return string
     */
    public function getTableInfoStr($tableName)
    {
        $result = '';

        $metadata = new Metadata($this->db);

        // get the table names
        $tableNames = $metadata->getTableNames();

        $table = $metadata->getTable($tableName);


        $result .= '    With columns: ' . PHP_EOL;
        foreach ($table->getColumns() as $column) {
            $result .= '        ' . $column->getName()
                    . ' -> ' . $column->getDataType()
                    . PHP_EOL;
        }

        $result .= PHP_EOL;
        $result .= '    With constraints: ' . PHP_EOL;

        foreach ($metadata->getConstraints($tableName) as $constraint) {

            /** @var $constraint Zend\Db\Metadata\Object\ConstraintObject */
            $result .= '        ' . $constraint->getName()
                    . ' -> ' . $constraint->getType()
                    . PHP_EOL;
            if (!$constraint->hasColumns()) {
                continue;
            }
            $result .= '            column: ' . implode(', ', $constraint->getColumns());
            if ($constraint->isForeignKey()) {
                $fkCols = array();
                foreach ($constraint->getReferencedColumns() as $refColumn) {
                    $fkCols[] = $constraint->getReferencedTableName() . '.' . $refColumn;
                }
                $result .= ' => ' . implode(', ', $fkCols);
            }

            return $result;
        }
    }

    /**
     * Checks if table exists
     *
     * @param string $tableName
     * @return bool
     */
    public function hasTable($tableName)
    {
        $dbMetadata = Source\Factory::createSourceFromAdapter($this->db);
        $tableNames = $dbMetadata->getTableNames();
        return in_array($tableName, $tableNames);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getTableConfig($configName)
    {
        $config = $this->getConfig();
        return $config[self::KEY_TABLES_CONFIGS][$configName];
    }

    /**
     *
     * @param type $tableData [tableName]
     */
    protected function create($tableName, $tableConfig)
    {
        if (is_string($tableConfig)) {
            $tableConfig = $this->config[self::KEY_TABLES_CONFIGS][$tableConfig];
        }
        $table = new CreateTable($tableName);
        foreach ($tableConfig as $fildName => $fildData) {
            $fildType = $fildData[self::FILD_TYPE];
            switch (true) {
                case in_array($fildType, $this->fildClasses['Colum']):
                    $fildParamsDefault = $this->parameters['Colum'];
                    break;
                case in_array($fildType, $this->fildClasses['LengthColumn']):
                    $fildParamsDefault = $this->parameters['LengthColumn'];
                    break;
                case in_array($fildType, $this->fildClasses['PrecisionColumn']):
                    $fildParamsDefault = $this->parameters['PrecisionColumn'];
                    break;
                default:
                    throw new RestException('Unknown fild type:' . $fildType);
            }
            $fildParams = [];
            foreach ($fildParamsDefault as $key => $value) {
                if (key_exists($key, $fildData[self::FILD_PARAMS])) {
                    $fildParams[] = $fildData[self::FILD_PARAMS][$key];
                } else {
                    $fildParams[] = $value;
                }
            }
            array_unshift($fildParams, $fildName);
            $fildClass = '\\Zend\\Db\\Sql\\Ddl\\Column\\' . $fildType;
            $reflectionObject = new \ReflectionClass($fildClass);
            $fildInstance = $reflectionObject->newInstanceArgs($fildParams); // it' like new class($callParamsArray[1], $callParamsArray[2]...)
            $table->addColumn($fildInstance);
        }

        $table->addConstraint(new Constraint\PrimaryKey('id'));


        $ctdMysql = new Sql\Platform\Mysql\Ddl\CreateTableDecorator();
        $mySqlPlatformDbAdapter = new Adapter\Platform\Mysql();
        $mySqlPlatformDbAdapter->setDriver($this->db->getDriver());
        $sqlString = $ctdMysql->setSubject($table)->getSqlString($mySqlPlatformDbAdapter);

        // this is  simplier version, not MySQL only, but withot options[] support
        //$mySqlPlatformSql = new Sql\Platform\Mysql\Mysql();
        //$sql = new Sql\Sql($this->db, null, $mySqlPlatformSql);
        //$sqlString = $sql->buildSqlString($table);

        return $this->db->query(
                        $sqlString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
        );
    }

}
