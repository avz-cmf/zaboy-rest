<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.10.16
 * Time: 12:19 PM
 */

namespace zaboy\rest\DataStore\Composite;


use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Query;
use zaboy\rest\DataStore\ConditionBuilder\SqlConditionBuilder;
use zaboy\rest\DataStore\DataStoreException;
use zaboy\rest\DataStore\DbTable;
use zaboy\rest\RqlParser\AggregateFunctionNode;
use zaboy\rest\TableGateway\TableManagerMysql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Source\Factory;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

class Composite extends DbTable
{

    /**
     * @var array
     * [
     *      'single' => [],
     *      'multiple' => []
     * ]
     */
    protected $boundTables;

    protected function initBound(){
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $tableManager = new TableManagerMysql($adapter);
        $metadata = Factory::createSourceFromAdapter($adapter);

        /** @var $constraint \Zend\Db\Metadata\Object\ConstraintObject */
        foreach($metadata->getConstraints($this->dbTable->table) as $constraint) {
            if ($constraint->isForeignKey()) {
                $this->boundTables['single'][$constraint->getReferencedTableName()] = [
                    'table' => new Composite(new TableGateway($constraint->getReferencedTableName(), $adapter)),
                    'column' => $constraint->getColumns()[0]
                ];
            }
        }

        foreach ($tableManager->getLinkedTables($this->dbTable->table) as $linkedTable){
            $this->boundTables['multiple'][$linkedTable['TABLE_NAME']] = [
                'table' => new Composite(new TableGateway($linkedTable['TABLE_NAME'], $adapter)),
                'column' => $linkedTable['COLUMN_NAME']
                ];
        }
    }

    public function query(Query $query)
    {
        $selectSQL = $this->dbTable->getSql()->select();
        $selectSQL = $this->setSelectColumns($selectSQL, $query);

        $fields = $selectSQL->getRawState(Select::COLUMNS);
        $bounds = [];
        if (isset($fields['.bounds.'])) {
            $bounds = $fields['.bounds.'];
            unset($fields);
        }


        $sql = $this->getSqlQuery($query);
        /** @var Adapter $adapter */
        $adapter = $this->dbTable->getAdapter();
        $rowset = $adapter->query($sql, $adapter::QUERY_MODE_EXECUTE);

        $data = $rowset->toArray();

        //todo: change call place
        $this->initBound();

        if(!empty($bounds)){
            foreach ($data as &$item){
                if(isset($bounds['nested'])){
                    foreach ($bounds['nested'] as $bound){
                        $match = [];
                        if(preg_match_all('/([\w]+)\.#/', $bound, $match)){
                            $name = $match[1][1];
                            if (isset($this->boundTables['multiple'][$name])) {
                                /** @var Composite $composite */
                                $composite = $this->boundTables['multiple'][$name]['table'];
                                $composite->initBound();

                                $boundQuery = new Query();
                                $boundQuery->setQuery(new EqNode(
                                    $this->boundTables['multiple'][$name]['column'],
                                    $item[$this->getIdentifier()]
                                ));
                                $item[$bound] = $composite->query($query);
                            }else if (isset($this->boundTables['single'][$name])){
                                $composite = $this->boundTables['single'][$name]['table'];
                                $composite->initBound();

                                $result = $composite->read($item[$this->getIdentifier()]);
                                if(isset($result)){
                                    foreach ($result as $key => $value){
                                        $item[$key] = $value;
                                    }
                                }
                            }
                        }
                    }
                }
                if(isset($bounds['own'])){
                    foreach ($bounds['own'] as $bound){
                        $match = [];
                        if(preg_match_all('/([\w]+)\./', $bound, $match)){
                            $name = $match[1][0];
                            if (isset($this->boundTables['multiple'][$name])) {
                                /** @var Composite $composite */
                                $composite = $this->boundTables['multiple'][$name]['table'];

                                $boundQuery = new Query();
                                $boundQuery->setQuery(new EqNode(
                                    $this->boundTables['multiple'][$name]['column'],
                                    $item[$this->getIdentifier()]
                                ));
                                $item[$bound] = $composite->query($query);
                            }else if (isset($this->boundTables['single'][$name])){
                                $composite = $this->boundTables['single'][$name]['table'];

                                $result = $composite->read($item[$this->boundTables['single'][$name]['column']]);
                                if(isset($result)){
                                    foreach ($result as $key => $value){
                                        if($key != $this->getIdentifier())
                                        $item[$key] = $value;
                                    }
                                }
                                unset($item[$this->boundTables['single'][$name]['column']]);
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    protected function setSelectColumns(Select $selectSQL, Query $query)
    {
        $select = $query->getSelect();
        $selectField = !$select ? [] : $select->getFields();
        $fields = [];
        if(!empty($selectField)){
            $bounds = [];
            $hawAggregate = false;
            $hawBound = false;
            foreach ($selectField as $field) {
                if ($field instanceof AggregateFunctionNode){
                    $hawAggregate = true;
                    //todo: create aggregate
                }else if (stristr($field, '.')){
                    //todo: rewrite parsing bound str
                    $hawBound = true;
                    if (stristr($field, '#')) {
                        $bounds['nested'][] = $field;
                    }else {
                        $bounds['own'][] = $field;
                    }
                }else {
                    $fields[] = $field;
                }
                if ($hawAggregate && $hawBound) {
                    throw new DataStoreException('Cannot use aggregate function with bounds');
                }
            }
            if (!empty($bounds)){
                $fields['.bounds.'] = $bounds;
            }
        }
        $selectSQL->columns(empty($fields) ? [Select::SQL_STAR] : $fields);
        return $selectSQL;
    }

    public function getSqlQuery(Query $query)
    {
        $conditionBuilder = new SqlConditionBuilder($this->dbTable->getAdapter(), $this->dbTable->getTable());

        $selectSQL = $this->dbTable->getSql()->select();
        $selectSQL->where($conditionBuilder($query->getQuery()));
        $selectSQL = $this->setSelectOrder($selectSQL, $query);
        $selectSQL = $this->setSelectLimitOffset($selectSQL, $query);
        $selectSQL = $this->setSelectColumns($selectSQL, $query);

        $selectSQL = $this->setSelectJoin($selectSQL, $query);

        $fields = $selectSQL->getRawState(Select::COLUMNS);
        if (isset($fields['.bounds.'])) {
            unset($fields['.bounds.']);
            if(empty($fields)){
                $fields = [Select::SQL_STAR];
            }
            $selectSQL->columns($fields);
        }

        $selectSQL = $this->makeExternalSql($selectSQL);

        //build sql string
        $sql = $this->dbTable->getSql()->buildSqlString($selectSQL);
        //replace double ` char to single.
        $sql = str_replace(["`(", ")`", "``"], ['(', ')', "`"], $sql);
        return $sql;
    }

    public function create($itemData, $rewriteIfExist = false)
    {
        return;
    }

    public function _create($itemData, $rewriteIfExist = false)
    {
        return;
    }

    public function _update($itemData, $createIfAbsent = false)
    {
        return;
    }

    public function update($itemData, $createIfAbsent = false)
    {
        return;
    }

    public function delete($id)
    {
        return;
    }

    public function deleteAll()
    {
        return;
    }
}