<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore\Iterators;

use zaboy\rest\DataStore\Interfaces\ReadInterface;
use Xiag\Rql\Parser\Node\Query\ScalarOperator;
use Xiag\Rql\Parser\Query;
use Xiag\Rql\Parser\Node;

/**
 * Outer iterator for zaboy\rest\DataStore\Read\ReadInterface objects
 *
 * @category   rest
 * @package    zaboy
 */
class DataStoreIterator implements \Iterator
{

    /**
     * pointer for current item in iteration
     *
     * @see Iterator
     * @var mixed $index
     */
    protected $index = null;

    /**
     *
     * @see Iterator
     * @var ReadInterface $dataStores
     */
    protected $dataStore;

    /**
     *
     * @see Iterator
     * @param ReadInterface $dataStore
     */
    public function __construct(ReadInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    /**
     *
     * @see Iterator
     * @return void
     */
    public function rewind()
    {
        $identifier = $this->dataStore->getIdentifier();
        $query = new Query();
        $selectNode = new Node\SelectNode([$identifier]);
        $query->setSelect($selectNode);
        $sortNode = new Node\SortNode([$identifier => 1]);
        $query->setSort($sortNode);
        $limitNode = new Node\LimitNode(1, 0);
        $query->setLimit($limitNode);
        $queryArray = $this->dataStore->query($query);
        $this->index = $queryArray[0][$identifier];
        $this->index = $queryArray === [] ? null : $queryArray[0][$identifier];
    }

    /**
     *
     * @see Iterator
     * @return array
     */
    public function current()
    {
        $result = isset($this->index) ? $this->dataStore->read($this->index) : null;
        return $result;
    }

    /**
     *
     * @see Iterator
     * @return int|string
     */
    public function key()
    {
        return $this->index;
    }

    /**
     *
     * @see Iterator
     * @return array
     */
    public function next()
    {
        $identifier = $this->dataStore->getIdentifier();
        $query = new Query();
        $selectNode = new Node\SelectNode([$identifier]);
        $query->setSelect($selectNode);
        $sortNode = new Node\SortNode([$identifier => 1]);
        $query->setSort($sortNode);
        $limitNode = new Node\LimitNode(1, 0);
        $query->setLimit($limitNode);
        $gtNode = new ScalarOperator\GtNode($identifier, $this->index);
        $query->setQuery($gtNode);
        $queryArray = $this->dataStore->query($query);
        $this->index = $queryArray === [] ? null : $queryArray[0][$identifier];
    }

    /**
     *
     * @see Iterator
     * @return bool
     */
    public function valid()
    {
        return isset($this->index) && ($this->dataStore->read($this->index) !== null);
    }

}
