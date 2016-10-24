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
use zaboy\rest\DataStore\Eav\Prop;

/**
 *
 * Add to config:
 * <code>
 *     'dataStore' => [
 *         'SomeResourceName' => [
 *             'class' => Entitiy::class,
 *             'tableName' => 'table_some_resource_name'
 *         ],
 *     ],
 * </code>
 *
 * Tablet 'able_some_resource_name' must be exist. Add code to  Eav\installer for create it.
 *
 */
class Entity extends DbTable
{

    public function getEntityName()
    {
        $tableName = $this->dbTable->table;
        return SysEntities::getEntityName($tableName);
    }

    public function getEntityTableName()
    {
        return $tableName = $this->dbTable->table;
    }

    public function create($itemData, $rewriteIfExist = false)
    {
        //Check props in $itemData filed and generate propsTableGateway
        //$this->propsTableGatewayInit($itemData);

        $identifier = $this->getIdentifier();
        $adapter = $this->dbTable->getAdapter();
        $adapter->getDriver()->getConnection()->beginTransaction();
        $propsData = [];
        $props = [];
        foreach ($itemData as $key => $value) {
            if (strpos($key, SysEntities::PROP_PREFIX) === 0) {
                $propTableName = explode('.', $key)[0];
                $props[$key] = new Prop(new TableGateway($propTableName, $adapter));
                $propsData[$key] = $value;
                unset($itemData[$key]);
            }
        }
        try {
            $sysEntities = new SysEntities(new TableGateway(SysEntities::TABLE_NAME, $adapter));
            $itemData = $sysEntities->prepareEntityCreate($this->getEntityName(), $itemData);
            $itemInserted = parent::create($itemData, false);

            if (!empty($itemInserted)) {
                foreach ($props as $key => $prop) {
                    $prop->createWithEntity($propsData[$key], $itemInserted[$identifier], $this->getEntityName(), $key);
                }
                $adapter->getDriver()->getConnection()->commit();
            } else {
                throw new DataStoreException('Not all data has been inserted. -> rollback');
            }
        } catch (\Exception $e) {
            $adapter->getDriver()->getConnection()->rollback();
            throw new DataStoreException("", 0, $e);
        }
        return $itemInserted;
    }

    protected function setSelectColumns(Select $selectSQL, Query $query)
    {
        $select = $query->getSelect();  //What fields will return
        $selectFields = !$select ? [] : $select->getFields();
        if (!empty($selectFields)) {
            $fields = [];

            foreach ($selectFields as $field) {
                if ($field instanceof AggregateFunctionNode) {
                    $fields[$field->getField() . "->" . $field->getFunction()] = new Expression($field->__toString());
                } else if (substr($field, 0, 1) == '@') {
                    /** @var TableGateway $prop */
                    $prop = $this->propsTableGateway[$field];
                    $selectSql->join(
                            $prop->getTable()
                            , [$this->entityName . '_id' => 'id']
                            , Select::SQL_STAR, Select::JOIN_LEFT
                    );
                } else {
                    $fields[] = $field;
                }
            }
            $selectSQL->columns($fields);
        }
        return $selectSQL;
    }

    protected function setSelectJoin(Select $selectSQL, Query $query)
    {
        $selectSQL->join(
                $this->dbTable->table
                , $sysEntitiesTableGateway->table . '.' . $this->getIdentifier() . ' = ' . $this->dbTable->table . '.' . $this->getIdentifier()
                , Select::SQL_STAR, Select::JOIN_LEFT
        );
        return $selectSQL;
    }

}
