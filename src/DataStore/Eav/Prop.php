<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore\Eav;

use zaboy\rest\DataStore\DbTable;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use zaboy\rest\DataStore\ConditionBuilder\SqlConditionBuilder;
use zaboy\rest\DataStore\DataStoreAbstract;
use zaboy\rest\DataStore\DataStoreException;
use zaboy\rest\RqlParser\AggregateFunctionNode;
use zaboy\rest\TableGateway\TableManagerMysql as TableManager;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;
use zaboy\rest\DataStore\Eav\SysEntities;
use Zend\Db\Metadata\Source\Factory;
use zaboy\rest\DataStore\DataStoreException;

/**
 *
 * Add to config:
 * <code>
 *     'dataStore' => [
 *         'SomeResourceName' => [
 *             'class' => Prop::class,
 *             'tableName' => 'table_some_resource_name'
 *         ],
 *     ],
 * </code>
 *
 * Tablet 'able_some_resource_name' must be exist. Add code to  Eav\installer for create it.
 *
 */
class Prop extends DbTable
{

    public function createWithEntity($propData, $entityId, $entityName, $propColumn)
    {
        $linkedColumn = $this->getLinkedColumn($entityName, $propColumn);
        if (is_null($linkedColumn)) {
            throwException(new DataStoreException('Wrong linked column: ' . $propColumn));
        }
        $this->dbTable->delete([$linkedColumn => $entityId]);
        foreach ($propData as $row) {
            $row[$linkedColumn] = $entityId;
            $result[] = $this->create($row);
        }
        return $result;
    }

    public function getPropName()
    {
        $tableName = $this->dbTable->table;
        return SysEntities::getPropName($tableName);
    }

    public function getPropTableName()
    {
        return $tableName = $this->dbTable->table;
    }

    public function getColumnsNames()
    {
        $adapter = $this->dbTable->adapter;
        $metadata = Factory::createSourceFromAdapter($adapter);
        $tableMetadata = $metadata->getTable($this->dbTable->table);
        $columns = $tableMetadata->getColumns();
        foreach ($columns as $column) {
            $columnsNames[] = $column->getName();
        }
        return $columnsNames;
    }

    public function getLinkedColumn($entityName, $propColumn)
    {
        $columnsNames = $this->getColumnsNames();

        //'prop_name.column_name' or 'prop_name'
        $linkedColumn = !strpos($propColumn, '.') ?
                //prop_name.column_name
                (key_exists(explode('.', $propColumn)[1], $columnsNames) ?
                        //column_name
                        key_exists(explode('.', $propColumn)[1]) :
                        //error
                        null
                ) :
                //prop_name
                (key_exists($entityName . SysEntities::ID_SUFFIX, $columnsNames) ?
                        //entity_id
                        $entityName . SysEntities::ID_SUFFIX :
                        (key_exists(SysEntities::TABLE_NAME . SysEntities::ID_SUFFIX, $columnsNames) ?
                                //sys_entities_id
                                SysEntities::TABLE_NAME . SysEntities::ID_SUFFIX :
                                //error
                                null
                        )
                );
        return $linkedColumn;
    }

}
