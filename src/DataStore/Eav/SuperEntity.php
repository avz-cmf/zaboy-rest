<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore\Eav;

use Xiag\Rql\Parser\Query;
use Zend\Db\Adapter\AdapterInterface;
use zaboy\rest\DataStore\DataStoreAbstract;
use zaboy\rest\DataStore\Interfaces\SqlQueryGetterInterface;
use zaboy\rest\DataStore\ConditionBuilder\SqlConditionBuilder;
use Zend\Db\Sql\Sql;
use zaboy\rest\DataStore\Eav\SysEntities;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use zaboy\rest\DataStore\DbTable;

class SuperEntity extends DbTable implements SqlQueryGetterInterface
{

    const LEFT_JOIN = '~';

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

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function query(Query $query)
    {
        $sql = $this->getSqlQuery($query);
        $adapter = $this->dbTable->getAdapter();
        $rowset = $adapter->query($sql, $adapter::QUERY_MODE_EXECUTE);

        return $rowset->toArray();
    }

    public function create($itemData, $rewriteIfExist = false)
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

        $last = array_shift($this->joinedEntities);
        $selectSQL->join(
                $last->dbTable->table
                , $last->dbTable->table . '.' . $identifier . '=' . $this->dbTable->table . '.' . $identifier
                , Select::SQL_STAR
                , Select::JOIN_RIGHT
        );
        foreach ($this->joinedEntities as $next) {
            if (!is_object($next)) {
                continue;
            }
            /* @var $next DbTable */
            $selectSQL->join(
                    $next->dbTable->table
                    , $last->dbTable->table . '.' . $identifier . '=' . $next->dbTable->table . '.' . $identifier
                    , Select::SQL_STAR
                    , Select::JOIN_LEFT
            );
            $last = $next;
        }

        //build sql string
        $sql = $this->dbTable->getSql()->buildSqlString($selectSQL);
        //replace double ` char to single.
        $sql = str_replace(["`(", ")`", "``"], ['(', ')', "`"], $sql);
        return $sql;
    }

}
