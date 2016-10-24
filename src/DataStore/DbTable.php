<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore;

use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use zaboy\rest\DataStore\ConditionBuilder\SqlConditionBuilder;
use zaboy\rest\RqlParser\AggregateFunctionNode;
use zaboy\rest\RqlParser\XSelectNode;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

/**
 * DataStores as Db Table
 *
 * @todo rearrangement query. Use TableGateway method instead string manipulation for compatible
 * @uses zend-db
 * @see https://github.com/zendframework/zend-db
 * @see http://en.wikipedia.org/wiki/Create,_read,_update_and_delete
 * @category   rest
 * @package    zaboy
 */
class DbTable extends DataStoreAbstract
{

    /**
     *
     * @var TableGateway
     */
    protected $dbTable;

    /**
     *
     * @param TableGateway $dbTable
     */
    public function __construct(TableGateway $dbTable)
    {
        $this->dbTable = $dbTable;
    }

//** Interface "zaboy\rest\DataStore\Interfaces\ReadInterface" **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function create($itemData, $rewriteIfExist = false)
    {

        $identifier = $this->getIdentifier();
        $adapter = $this->dbTable->getAdapter();


        if (is_int(array_keys($itemData)[0])) {
            $query = new Query();
            $query->setSelect(new XSelectNode([new AggregateFunctionNode('max', $this->getIdentifier())]));
            $prewId = $this->query($query)[0][$this->getIdentifier() . '->max'];
        }

        // begin Transaction
        $errorMsg = 'Can\'t start insert transaction';

        $adapter->getDriver()->getConnection()->beginTransaction();
        try {
            if (isset($itemData[$identifier]) && $rewriteIfExist) {
                $errorMsg = 'Can\'t delete item with "id" = ' . $itemData[$identifier];
                $this->dbTable->delete(array($identifier => $itemData[$identifier]));
            }
            $errorMsg = 'Can\'t insert item';
            $rowsCount = $this->dbTable->insert($itemData);
            $adapter->getDriver()->getConnection()->commit();
        } catch (\Exception $e) {
            $adapter->getDriver()->getConnection()->rollback();
            throw new DataStoreException($errorMsg, 0, $e);
        }


        if ($rowsCount > 1) {
            $newItem = [];
            $lastId = $this->query($query)[0][$this->getIdentifier() . '->max'];
            foreach (range($prewId + 1, $lastId) as $id) {
                $newItem[] = [$identifier => $id];
            }
        } else {
            $id = $this->dbTable->lastInsertValue;
            $newItem = array_merge(array($identifier => $id), $itemData);
        }


        return $newItem;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function query(Query $query)
    {

        $conditionBuilder = new SqlConditionBuilder($this->dbTable->getAdapter());

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
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $rowset = $adapter->query($sql, $adapter::QUERY_MODE_EXECUTE);

        return $rowset->toArray();
    }

    protected function setSelectLimitOffset(Select $selectSQL, Query $query)
    {
        $limits = $query->getLimit();
        $limit = !$limits ? self::LIMIT_INFINITY : $limits->getLimit();
        $offset = !$limits ? 0 : $limits->getOffset();
        if ($limit <> self::LIMIT_INFINITY) {
            $selectSQL->limit($limit);
        }
        if ($offset <> 0) {
            $selectSQL->offset($offset);
        }
        return $selectSQL;
    }

    protected function setSelectOrder(Select $selectSQL, Query $query)
    {
        $sort = $query->getSort();
        $sortFields = !$sort ? [$this->dbTable->table . '.' . $this->getIdentifier() => SortNode::SORT_ASC] : $sort->getFields();
        foreach ($sortFields as $ordKey => $ordVal) {
            if (!preg_match('/[\w]+\.[\w]+/', $ordKey)) {
                $ordKey = $this->dbTable->table . '.' . $ordKey;
            }
            if ((int) $ordVal === SortNode::SORT_DESC) {
                $selectSQL->order($ordKey . ' ' . Select::ORDER_DESCENDING);
            } else {
                $selectSQL->order($ordKey . ' ' . Select::ORDER_ASCENDING);
            }
        }
        return $selectSQL;
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
        return $selectSQL;
    }

    protected function makeExternalSql(Select $selectSQL)
    {
        //create new Select - for aggregate func query
        $fields = $selectSQL->getRawState(Select::COLUMNS);
        $hasAggregateFilds = array_keys($fields) != range(0, sizeof($fields) - 1);
        if ($hasAggregateFilds) {
            $externalSql = new Select();
            $externalSql->columns($selectSQL->getRawState(Select::COLUMNS));
            //change select column to all
            $selectSQL->columns(['*']);
            //create sub query without aggreagate func and with all fields
            $from = "(" . $this->dbTable->getSql()->buildSqlString($selectSQL) . ")";
            $externalSql->from(array('Q' => $from));
            return $externalSql;
        } else {
            return $selectSQL;
        }
    }

// ** Interface "zaboy\rest\DataStore\Interfaces\DataStoresInterface"  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        $identifier = $this->getIdentifier();
        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must has primary key');
        }
        $id = $itemData[$identifier];
        $this->checkIdentifierType($id);
        $adapter = $this->dbTable->getAdapter();
        $errorMsg = 'Can\'t update item with "id" = ' . $id;
        $queryStr = 'SELECT ' . Select::SQL_STAR
                . ' FROM ' . $adapter->platform->quoteIdentifier($this->dbTable->getTable())
                . ' WHERE ' . $adapter->platform->quoteIdentifier($identifier) . ' = ?'
                . ' FOR UPDATE';
        $adapter->getDriver()->getConnection()->beginTransaction();
        try {
            //is row with this index exist?
            $rowset = $adapter->query($queryStr, array($id));
            $isExist = !is_null($rowset->current());
            switch (true) {
                case!$isExist && !$createIfAbsent:
                    throw new DataStoreException($errorMsg);
                case!$isExist && $createIfAbsent:
                    $this->dbTable->insert($itemData);
                    $result = $itemData;
                    break;
                case $isExist:
                    unset($itemData[$identifier]);
                    $this->dbTable->update($itemData, array($identifier => $id));
                    $rowset = $adapter->query($queryStr, array($id));
                    $result = $rowset->current()->getArrayCopy();
                    break;
            }
            $adapter->getDriver()->getConnection()->commit();
        } catch (\Exception $e) {
            $adapter->getDriver()->getConnection()->rollback();
            throw new DataStoreException($errorMsg, 0, $e);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $identifier = $this->getIdentifier();
        $this->checkIdentifierType($id);

        $element = $this->read($id);

        $deletedItemsCount = $this->dbTable->delete(array($identifier => $id));
        return $element;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function read($id)
    {
        $this->checkIdentifierType($id);
        $identifier = $this->getIdentifier();
        $rowset = $this->dbTable->select(array($identifier => $id));
        $row = $rowset->current();
        if (isset($row)) {
            return $row->getArrayCopy();
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $where = '1=1';
        $deletedItemsCount = $this->dbTable->delete($where);
        return $deletedItemsCount;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function count()
    {
        $adapter = $this->dbTable->getAdapter();
        /* @var $rowset ResultSet */
        $rowset = $adapter->query(
                'SELECT COUNT(*) AS count FROM '
                . $adapter->platform->quoteIdentifier($this->dbTable->getTable())
                , $adapter::QUERY_MODE_EXECUTE);
        return $rowset->current()['count'];
    }

// ** protected  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    protected function getKeys()
    {
        $identifier = $this->getIdentifier();
        $select = $this->dbTable->getSql()->select();
        $select->columns(array($identifier));
        $rowset = $this->dbTable->selectWith($select);
        $keysArrays = $rowset->toArray();
        if (PHP_VERSION_ID >= 50500) {
            $keys = array_column($keysArrays, $identifier);
        } else {
            $keys = array();
            foreach ($keysArrays as $value) {
                $keys[] = $value[$identifier];
            }
        }
        return $keys;
    }

}
