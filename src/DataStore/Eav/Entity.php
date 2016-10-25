<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore\Eav;

use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;
use zaboy\rest\DataStore\ConditionBuilder\SqlConditionBuilder;
use zaboy\rest\DataStore\DataStoreException;
use zaboy\rest\DataStore\DbTable;
use zaboy\rest\RqlParser\AggregateFunctionNode;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

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

    //TODO: во время метода parent::create мы закроем транзакцию и если возникнет исключение мы не сможем вызвать rollback
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
            $itemData = $sysEntities->prepareEntityCreate($this->getEntityName(), $itemData, $rewriteIfExist);
            $itemInserted = parent::create($itemData, false);

            if (!empty($itemInserted)) {
                /**
                 * @var string $key
                 * @var Prop $prop
                 */
                foreach ($props as $key => $prop) {
                    $itemInserted[$key] = $prop->createWithEntity($propsData[$key], $itemInserted[$identifier], $this->getEntityName(), $key);
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

    public function update($itemData, $createIfAbsent = false)
    {
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
            if ($createIfAbsent) {
                throw new DataStoreException("This method dosn't work with flag $createIfAbsent = true");
            }
            $itemInserted = parent::update($itemData, false);
            if (!empty($itemInserted)) {
                /**
                 * @var string $key
                 * @var  Prop $prop
                 */

                foreach ($props as $key => $prop) {
                    $propQuery = new Query();
                    $propQuery->setQuery(
                        new EqNode($prop->getLinkedColumn($this->getEntityName(), $key), $itemInserted[$identifier]));
                    $propQuery->setSelect(new SelectNode([$prop->getIdentifier()]));
                    $allEntityProp = $prop->query($propQuery);

                    foreach ($allEntityProp as $entityPropItem) {
                        $find = false;
                        foreach ($propsData[$key] as &$propDataItem) {
                            if (isset($propDataItem[$prop->getIdentifier()]) &&
                                $entityPropItem[$prop->getIdentifier()] === $propDataItem[$prop->getIdentifier()]
                            ) {
                                $find = true;
                                $diff = array_diff_assoc($entityPropItem, $propDataItem);
                                if (empty($diff) || count($propDataItem) == 1) {
                                    unset($propDataItem);
                                }
                                break;
                            }
                        }
                        if (!$find) {
                            $prop->delete($entityPropItem[$prop->getIdentifier()]);
                        }
                    }
                    $prop->updateWithEntity($propsData[$key], $itemInserted[$identifier], $this->getEntityName(), $key);

                    $propQuery->setSelect(new SelectNode());
                    $allEntityProp = $prop->query($propQuery);
                    $itemInserted[$key] = $allEntityProp;
                }
            }
            $adapter->getDriver()->getConnection()->commit();
        } catch (\Exception $e) {
            $adapter->getDriver()->getConnection()->rollback();
            throw new DataStoreException("", 0, $e);
        }
        return $itemInserted;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function query(Query $query)
    {

        $conditionBuilder = new SqlConditionBuilder($this->dbTable->getAdapter(), $this->dbTable->getTable());

        $selectSQL = $this->dbTable->getSql()->select();
        $selectSQL->where($conditionBuilder($query->getQuery()));
        $selectSQL = $this->setSelectOrder($selectSQL, $query);
        $selectSQL = $this->setSelectLimitOffset($selectSQL, $query);
        $selectSQL = $this->setSelectColumns($selectSQL, $query);

        $fields = $selectSQL->getRawState(Select::COLUMNS);
        $props = [];
        if (isset($fields['props'])) {
            $props = $fields['props'];
            unset($fields['props']);
            $selectSQL->columns($fields);
        }

        $selectSQL = $this->setSelectJoin($selectSQL, $query);
        $selectSQL = $this->makeExternalSql($selectSQL);

        //build sql string
        $sql = $this->dbTable->getSql()->buildSqlString($selectSQL);
        //replace double ` char to single.
        $sql = str_replace(["`(", ")`", "``"], ['(', ')', "`"], $sql);
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $rowset = $adapter->query($sql, $adapter::QUERY_MODE_EXECUTE);

        $data = $rowset->toArray();
        if (!empty($props)) {
            foreach ($data as &$item) {
                /** @var $prop Prop */
                foreach ($props as $key => $prop) {
                    $linkedColumn = $prop->getLinkedColumn($this->getEntityName(), $key);
                    $propQuery = new Query();
                    $propQuery->setQuery(new EqNode($linkedColumn, $item[$this->getIdentifier()]));
                    $propData = $prop->query($propQuery);
                    $item[$key] = $propData;
                }
            }
        }
        return $data;
    }

    protected function setSelectColumns(Select $selectSQL, Query $query)
    {
        $select = $query->getSelect();  //What fields will return
        $selectFields = !$select ? [] : $select->getFields();
        $props = [];
        if (!empty($selectFields)) {
            $fields = [];
            $hawAggregate = false;
            $hawProps = false;
            foreach ($selectFields as $field) {
                if ($field instanceof AggregateFunctionNode) {
                    $fields[$field->getField() . "->" . $field->getFunction()] = new Expression($field->__toString());
                    $hawAggregate = true;
                } else if (strpos($field, SysEntities::PROP_PREFIX) === 0) {
                    $propTableName = explode('.', $field)[0];
                    $props[$field] = new Prop(new TableGateway($propTableName, $this->dbTable->getAdapter()));
                    $hawProps = true;
                } else {
                    $fields[] = $field;
                }
                if ($hawAggregate && $hawProps) {
                    throw new DataStoreException('Cannot use aggregate function with props');
                }
            }
            if (!empty($props)) {
                $fields['props'] = $props;
            }
            $selectSQL->columns($fields);
        }
        return $selectSQL;
    }

    protected function setSelectJoin(Select $selectSQL, Query $query)
    {
        $on = SysEntities::TABLE_NAME . '.' . $this->getIdentifier() . ' = ' . $this->getEntityTableName() . '.' . $this->getIdentifier();
        $selectSQL->join(
            SysEntities::TABLE_NAME
            , $on
            , Select::SQL_STAR, Select::JOIN_LEFT
        );
        return $selectSQL;
    }

    public function delete($id)
    {
        $sysEntities = new SysEntities(new TableGateway(SysEntities::TABLE_NAME, $this->dbTable->getAdapter()));
        return $sysEntities->delete($id);
    }

    public function deleteAll()
    {
        $sysEntities = new SysEntities(new TableGateway(SysEntities::TABLE_NAME, $this->dbTable->getAdapter()));
        return $sysEntities->deleteAllInEntity($this->getEntityName());
    }


}
