<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore;

use Xiag\Rql\Parser\Node;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use zaboy\rest\DataStore\Interfaces\DataStoresInterface;
use zaboy\rest\DataStore\Iterators\DataStoreIterator;
use zaboy\rest\RqlParser\AggregateFunctionNode;

/**
 * Abstract class for DataStores
 *
 * @todo make support null in eq(fieldname, null) and ne(fieldname, null)
 * @todo JsonSerializable https://github.com/zendframework/zend-diactoros/blob/master/doc/book/custom-responses.md#json-responses
 * @todo Adapter paras to config for tests
 * @todo Excel client
 * @todo CSV Store
 * @see http://en.wikipedia.org/wiki/Create,_read,_update_and_delete
 * @category   rest
 * @package    zaboy
 */
abstract class DataStoreAbstract implements DataStoresInterface
{

    /**
     *
     * @var \zaboy\rest\DataStore\ConditionBuilder\ConditionBuilderAbstract
     */
    protected $conditionBuilder;

//** Interface "zaboy\rest\DataStore\Interfaces\ReadInterface" **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function has($id)
    {
        return !(empty($this->read($id)));
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function read($id)
    {
        $identifier = $this->getIdentifier();
        $this->checkIdentifierType($id);
        $query = new Query();
        $eqNode = new EqNode($identifier, $id);
        $query->setQuery($eqNode);
        $queryResult = $this->query($query);
        if (empty($queryResult)) {
            return null;
        } else {
            return $queryResult[0];
        }
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return self::DEF_ID;
    }

    /**
     * Throw Exception if type of Identifier is wrong
     *
     * @param mix $id
     */
    protected function checkIdentifierType($id)
    {
        $idType = gettype($id);
        if ($idType == 'integer' || $idType == 'double' || $idType == 'string') {
            return;
        } else {
            throw new DataStoreException("Type of Identifier is wrong - " . $idType);
        }
    }

// ** Interface "zaboy\rest\DataStore\Interfaces\DataStoresInterface"  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function query(Query $query)
    {
        $limitNode = $query->getLimit();
        $limit = !$limitNode ? self::LIMIT_INFINITY : $query->getLimit()->getLimit();
        $offset = !$limitNode ? 0 : $query->getLimit()->getOffset();
        if (isset($limitNode) && $query->getSort() !== null) {
            $data = $this->queryWhere($query, self::LIMIT_INFINITY, 0);
            $sortedData = $this->querySort($data, $query);
            $result = array_slice($sortedData, $offset, $limit == self::LIMIT_INFINITY ? null : $limit);
        } else {
            $data = $this->queryWhere($query, $limit, $offset);
            $result = $this->querySort($data, $query);
        }
        return $this->querySelect($result, $query);
    }

    protected function queryWhere(Query $query, $limit, $offset)
    {
        $conditionBuilder = $this->conditionBuilder;
        $conditioon = $conditionBuilder($query->getQuery());
        $whereFunctionBody = PHP_EOL .
            '$result = ' . PHP_EOL
            . rtrim($conditioon, PHP_EOL) . ';' . PHP_EOL
            . 'return $result;';
        $whereFunction = create_function('$item', $whereFunctionBody);
        $suitableItemsNumber = 0;
        $result = [];
        foreach ($this as $value) {
            switch (true) {
                case (!($whereFunction($value))):
                    break; // skip!
                case $suitableItemsNumber < $offset:
                    $suitableItemsNumber = $suitableItemsNumber + 1;
                    break; // increment!
                case $limit <> self::LIMIT_INFINITY && $suitableItemsNumber >= ($limit + $offset):
                    return $result; //enough!
                default:
                    $result[] = $value; // write!
                    $suitableItemsNumber = $suitableItemsNumber + 1;
            }
        }
        return $result;
    }

    protected function querySort($data, Query $query)
    {
        if (empty($query->getSort())) {
            return $data;
        }
        $nextCompareLevel = '';
        $sortFields = $query->getSort()->getFields();
        foreach ($sortFields as $ordKey => $ordVal) {
            if ((int)$ordVal <> SortNode::SORT_ASC && (int)$ordVal <> SortNode::SORT_DESC) {
                throw new DataStoreException('Invalid condition: ' . $ordVal);
            }
            $cond = $ordVal == SortNode::SORT_DESC ? '<' : '>';
            $notCond = $ordVal == SortNode::SORT_ASC ? '<' : '>';

            $prevCompareLevel = "if (\$a['$ordKey'] $cond \$b['$ordKey']) {return 1;};" . PHP_EOL
                . "if (\$a['$ordKey'] $notCond  \$b['$ordKey']) {return -1;};" . PHP_EOL;
            $nextCompareLevel = $nextCompareLevel . $prevCompareLevel;
        }
        $sortFunctionBody = $nextCompareLevel . 'return 0;';
        $sortFunction = create_function('$a,$b', $sortFunctionBody);
        usort($data, $sortFunction);
        return $data;
    }

    protected function querySelect($data, Query $query)
    {
        $selectNode = $query->getSelect();
        if (empty($selectNode)) {
            return $data;
        } else {
            $resultArray = array();
            $compareArray = array();

            foreach ($selectNode->getFields() as $field) {
                if ($field instanceof AggregateFunctionNode) {
                    switch ($field->getFunction()) {
                        case 'count': {
                            $arr = [];
                            foreach ($data as $item) {
                                if (isset($item[$field->getField()])) {
                                    $arr[] = $item[$field->getField()];
                                }
                            }
                            $compareArray[$field->getField() . '->' . $field->getFunction()] = [count($arr)];
                            break;
                        }
                        case 'max': {
                            $max = 0;
                            foreach ($data as $item) {
                                if ($max < $item[$field->getField()]) {
                                    $max = $item[$field->getField()];
                                }
                            }
                            $compareArray[$field->getField() . '->' . $field->getFunction()] = [$max];
                            break;
                        }
                        case 'min': {
                            $min = null;
                            foreach ($data as $item) {
                                if(!isset($min)){
                                    $min = $item[$field->getField()];
                                }
                                if ($min > $item[$field->getField()]) {
                                    $min = $item[$field->getField()];
                                }
                            }
                            $compareArray[$field->getField() . '->' . $field->getFunction()] = [$min];
                            break;
                        }
                    }
                } else {
                    $dataLine = [];
                    foreach ($data as $item) {
                        $dataLine[] = $item[$field];
                    }
                    $compareArray[$field] = $dataLine;
                }
            }
            $min = null;
            foreach ($compareArray as $column) {
                if (!isset($min)) {
                    $min = count($column);
                } elseif (count($column) < $min) {
                    $min = count($column);
                }
            }
            for ($i = 0; $i < $min; ++$i) {
                $item = [];
                foreach ($compareArray as $fieldName => $column) {
                    $item[$fieldName] = $column[$i];
                }
                $resultArray[] = $item;
            }
            return $resultArray;
        }
    }

// ** Interface "/Coutable"  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    abstract public function create($itemData, $rewriteIfExist = false);

// ** Interface "/IteratorAggregate"  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    abstract public function update($itemData, $createIfAbsent = false);

// ** protected  **/

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        /* $keys = $this->getKeys();
         $deletedItemsNumber = 0;
         foreach ($keys as $id) {
             $deletedNumber = $this->delete($id);
             if (is_null($deletedNumber)) {
                 return null;
             }
             $deletedItemsNumber = $deletedItemsNumber + $deletedNumber;
         }
         return $deletedItemsNumber;*/

        $keys = $this->getKeys();
        $deletedItemsNumber = 0;
        foreach ($keys as $id) {
            $deletedItems = $this->delete($id);
            if (is_null($deletedItems)) {
                return null;
            }
            $deletedItemsNumber++;
        }
        return $deletedItemsNumber;
    }

    /**
     * Return array of keys or empty array
     *
     * @return array array of keys or empty array
     */
    protected function getKeys()
    {
        $identifier = $this->getIdentifier();
        $query = new Query();
        $selectNode = new Node\SelectNode([$identifier]);
        $query->setSelect($selectNode);
        $queryResult = $this->query($query);
        $keysArray = [];
        foreach ($queryResult as $row) {
            $keysArray[] = $row[$identifier];
        }
        return $keysArray;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    abstract public function delete($id);

    /**
     * Interface "/Coutable"
     *
     * @see /coutable
     * @return int
     */
    public function count()
    {
        $keys = $this->getKeys();
        return count($keys);
    }

    /**
     * Iterator for Interface IteratorAggregate
     *
     * @see \IteratorAggregate
     * @return \Iterator
     */
    public function getIterator()
    {
        return new DataStoreIterator($this);
    }

}
