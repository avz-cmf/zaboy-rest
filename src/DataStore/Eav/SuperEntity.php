<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore\Eav;

use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use zaboy\rest\DataStore\ConditionBuilder\SqlConditionBuilder;
use zaboy\rest\DataStore\DataStoreException;
use zaboy\rest\DataStore\DbTable;
use zaboy\rest\DataStore\Interfaces\SqlQueryGetterInterface;
use zaboy\rest\RqlParser\AggregateFunctionNode;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Source\Factory;
use Zend\Db\Sql\Ddl\Column\Column;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

class SuperEntity extends DbTable implements SqlQueryGetterInterface
{

    const INNER_JOIN = '~';

    /**
     *
     * @var array [$dataStoreObj1, '`', $dataStoreObj1, '`'...]
     */
    protected $joinedEntities;

    public function __construct(TableGateway $dbTable, $joinedEntities)
    {
        //$dbTable - TableGateway for SysEntities table
        parent::__construct($dbTable);
        $this->joinedEntities = $joinedEntities;
    }

    public function getEntityName()
    {
        $name = "";
        /** @var Entity $entity */
        foreach ($this->joinedEntities as $entity) {
            if (is_object($entity)) {
                $name .= $entity->getEntityName();
            } else {
                $name .= $entity;
            }
        }
        rtrim($name, SuperEntity::INNER_JOIN);
        return $name;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function query(Query $query)
    {
        $sql = $this->getSqlQuery($query);
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $rowSet = $adapter->query($sql, $adapter::QUERY_MODE_EXECUTE);

        return $rowSet->toArray();
    }

    public function create($itemData, $rewriteIfExist = false)
    {
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $adapter->getDriver()->getConnection()->beginTransaction();

        try {
            $sysEntity = new SysEntities($this->dbTable);
            $itemData = $sysEntity->prepareEntityCreate($this->getEntityName(), $itemData, $rewriteIfExist);
            $insertedItem = [];
            /** @var Entity $entity */
            foreach ($this->joinedEntities as $entity) {
                if (is_object($entity)) {
                    $metadata = Factory::createSourceFromAdapter($adapter);
                    $table = $metadata->getTable($entity->getEntityTableName());
                    $entityItem = [];
                    /** @var Column $column */
                    foreach ($table->getColumns() as $column) {
                        $entityItem[$column->getName()] = $itemData[$column->getName()];
                    }
                    $entityItem = $entity->_create($entityItem);
                    $insertedItem = array_merge($insertedItem, $entityItem);
                }
            }
            $adapter->getDriver()->getConnection()->commit();
        } catch (\Exception $e) {
            $adapter->getDriver()->getConnection()->rollback();
            throw new DataStoreException('Can\'t update item', 0, $e);
        }

        return $insertedItem;
    }

    public function update($itemData, $createIfAbsent = false)
    {
        return;
    }

    public function delete($id)
    {
        return;
    }

    public function getSqlQuery(Query $query)
    {
        $identifier = $this->getIdentifier();
        $conditionBuilder = new SqlConditionBuilder($this->dbTable->getAdapter(), $this->dbTable->getTable());

        $selectSQL = $this->dbTable->getSql()->select();
        $selectSQL->where($conditionBuilder($query->getQuery()));
        $selectSQL = $this->setSelectOrder($selectSQL, $query);
        $selectSQL = $this->setSelectLimitOffset($selectSQL, $query);
        $selectSQL = $this->setSelectColumns($selectSQL, $query);
        $selectSQL = $this->setSelectJoin($selectSQL, $query);
        $selectSQL = $this->makeExternalSql($selectSQL);

        //build sql string
        $sql = $this->dbTable->getSql()->buildSqlString($selectSQL);
        //replace double ` char to single.
        $sql = str_replace(["`(", ")`", "``"], ['(', ')', "`"], $sql);
        return $sql;
    }

    protected function setSelectColumns(Select $selectSQL, Query $query)
    {
        $select = $query->getSelect();
        $selectField = !$select ? [] : $select->getFields();

        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $metadata = Factory::createSourceFromAdapter($adapter);
        $sysEntityTable = $metadata->getTable(SysEntities::TABLE_NAME);
        if (!empty($selectField)) {
            $sysEntitiesFields = [];
            foreach ($selectField as $field) {
                if ($field instanceof AggregateFunctionNode) {
                    if (in_array($field->getField(), $sysEntityTable->getColumns())) {
                        $sysEntitiesFields[$field->getField() .
                        "->" . $field->getFunction()] = new Expression($field->__toString());
                    }
                } else {
                    if (in_array($field, $sysEntityTable->getColumns())) {
                        $sysEntitiesFields[] = $field;
                    }
                }
            }
            $selectSQL->columns($sysEntitiesFields);
        }
        return $selectSQL;
    }

    protected function setSelectJoin(Select $selectSQL, Query $query)
    {

        $select = $query->getSelect();
        $selectField = !$select ? [] : $select->getFields();

        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $metadata = Factory::createSourceFromAdapter($adapter);
        $sysEntityTable = $metadata->getTable(SysEntities::TABLE_NAME);
        $identifier = $this->getIdentifier();

        //todo: agregate function
        $joinedEntityfileds = [];
        if (!empty($selectField)) {
            foreach ($selectField as $field) {
                if ($field instanceof AggregateFunctionNode and !in_array($field->getField(), $sysEntityTable->getColumns())) {
                    $joinedEntityfileds[$field->getField() .
                    "->" . $field->getFunction()] = new Expression($field->__toString());
                } else if (!in_array($field, $sysEntityTable->getColumns())) {
                    $joinedEntityfileds[] = $field;
                }
            }
        }

        $prew = $this;
        /** @var DbTable $entity */
        foreach ($this->joinedEntities as $entity) {
            if (is_object($entity)) {
                $entityField = [];
                $entityTable = $metadata->getTable($entity->dbTable->table);
                /** @var Column $column */
                foreach ($entityTable->getColumns() as $column) {
                    $colName = $column->getName();
                    if (in_array($colName, $joinedEntityfileds)) {
                        $entityField[] = $colName;
                    }
                }
                $selectSQL->join(
                    $entity->dbTable->table,
                    $entity->dbTable->table . '.' . $identifier . '=' . $prew->dbTable->table . '.' . $identifier,
                    empty($entityField) ? Select::SQL_STAR : $entityField,
                    Select::JOIN_INNER
                );
                $prew = $entity;
            }
        }
        return $selectSQL;
    }

    protected function setSelectOrder(Select $selectSQL, Query $query)
    {
        $sort = $query->getSort();
        $sortFields = !$sort ? [$this->dbTable->table . '.' . $this->getIdentifier() => SortNode::SORT_ASC]
            : $sort->getFields();

        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $metadata = Factory::createSourceFromAdapter($adapter);

        foreach ($sortFields as $ordKey => $ordVal){
            if (!preg_match('/[\w]+\.[\w]+/', $ordKey)) {
                $fined = false;
                /** @var DbTable $entity */
                foreach ($this->joinedEntities as $entity){
                    if(is_object($entity)){
                        $entityTable = $metadata->getTable($entity->dbTable->table);
                        /** @var Column $column */
                        foreach ($entityTable->getColumns() as $column) {
                            if ($column->getName() == $ordKey){
                                $ordKey = $entity->dbTable->table . '.' . $ordKey;
                                $fined = true;
                                break;
                            }
                        }
                    }
                    if ($fined){
                        break;
                    }
                }
                if (!$fined){
                    $ordKey = $this->dbTable->table . '.' . $ordKey;
                }
            }
            if ((int)$ordVal === SortNode::SORT_DESC) {
                $selectSQL->order($ordKey . ' ' . Select::ORDER_DESCENDING);
            } else {
                $selectSQL->order($ordKey . ' ' . Select::ORDER_ASCENDING);
            }
        }
        return $selectSQL;
    }
}

































