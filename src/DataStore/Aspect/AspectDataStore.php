<?php

namespace zaboy\rest\DataStore\Aspect;

use Xiag\Rql\Parser\Query;
use zaboy\rest\DataStore\Interfaces\DataStoresInterface;

class AspectDataStore implements DataStoresInterface
{
    /** @var DataStoresInterface $dataStore */
    protected $dataStore;

    /**
     * AspectDataStoreAbstract constructor.
     *
     * @param DataStoresInterface $dataStore
     */
    public function __construct(DataStoresInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    /**
     * The pre-aspect for "getIterator".
     *
     * By default does nothing
     */
    protected function preGetIterator()
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->preGetIterator();
        $iterator = $this->dataStore->getIterator();
        $this->postGetIterator($iterator);
        return $iterator;
    }

    /**
     * The post-aspect for "getIterator"
     *
     * By default does nothing
     *
     * @param \Iterator $iterator
     */
    protected function postGetIterator(\Iterator &$iterator)
    {
    }

    /**
     * The pre-aspect for "create".
     *
     * By default does nothing
     *
     * @param $itemData
     * @param bool|false $rewriteIfExist
     */
    protected function preCreate(&$itemData, &$rewriteIfExist = false)
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        $this->preCreate($itemData, $rewriteIfExist);
        $result = $this->dataStore->create($itemData, $rewriteIfExist);
        $this->postCreate($result);
        return $result;
    }

    /**
     * The post-aspect for "create"
     *
     * By default does nothing
     *
     * @param $result
     */
    protected function postCreate(&$result)
    {
    }

    /**
     * The pre-aspect for "update".
     *
     * By default does nothing
     *
     * @param $itemData
     * @param bool|false $createIfAbsent
     */
    protected function preUpdate(&$itemData, &$createIfAbsent = false)
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        $this->preUpdate($itemData, $createIfAbsent);
        $result = $this->dataStore->update($itemData, $createIfAbsent);
        $this->postUpdate($result);
        return $result;
    }

    /**
     * The post-aspect for "update"
     *
     * By default does nothing
     *
     * @param mixed $result
     */
    protected function postUpdate(&$result)
    {
    }

    /**
     * The pre-aspect for "delete".
     *
     * By default does nothing
     *
     * @param $id
     */
    protected function preDelete(&$id)
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $this->preDelete($id);
        $result = $this->dataStore->delete($id);
        $this->postDelete($result);
        return $result;
    }

    /**
     * The post-aspect for "delete"
     *
     * By default does nothing
     *
     * @param mixed $result
     */
    protected function postDelete(&$result)
    {
    }

    /**
     * The pre-aspect for "deleteAll".
     *
     * By default does nothing
     */
    protected function preDeleteAll()
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $this->preDeleteAll();
        $result = $this->dataStore->deleteAll();
        $this->postDeleteAll($result);
        return $result;
    }

    /**
     * The post-aspect for "deleteAll"
     *
     * By default does nothing
     *
     * @param mixed $result
     */
    protected function postDeleteAll(&$result)
    {
    }

    /**
     * The pre-aspect for "getIdentifier".
     *
     * By default does nothing
     */
    protected function preGetIdentifier()
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        $this->preGetIdentifier();
        $result = $this->dataStore->getIdentifier();
        $this->postGetIdentifier($result);
        return $result;
    }

    /**
     * The post-aspect for "getIdentifier"
     *
     * By default does nothing
     *
     * @param mixed $result
     */
    protected function postGetIdentifier(&$result)
    {
    }

    /**
     * The pre-aspect for "read".
     *
     * By default does nothing
     *
     * @param $id
     */
    protected function preRead(&$id)
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function read($id)
    {
        $this->preRead($id);
        $result = $this->dataStore->read($id);
        $this->postRead($result);
        return $result;
    }

    /**
     * The post-aspect for "read"
     *
     * By default does nothing
     *
     * @param mixed $result
     */
    protected function postRead(&$result)
    {
    }

    /**
     * The pre-aspect for "has".
     *
     * By default does nothing
     *
     * @param $id
     */
    protected function preHas(&$id)
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function has($id)
    {
        $this->preHas($id);
        $result = $this->dataStore->has($id);
        $this->postHas($result);
        return $result;
    }

    /**
     * The post-aspect for "has"
     *
     * By default does nothing
     *
     * @param mixed $result
     */
    protected function postHas(&$result)
    {
    }

    /**
     * The pre-aspect for "query".
     *
     * By default does nothing
     *
     * @param Query $query
     */
    protected function preQuery(Query &$query)
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function query(Query $query)
    {
        $this->preQuery($query);
        $result = $this->dataStore->query($query);
        $this->postQuery($result);
        return $result;
    }

    /**
     * The post-aspect for "query"
     *
     * By default does nothing
     *
     * @param mixed $result
     */
    protected function postQuery(&$result)
    {
    }

    /**
     * The pre-aspect for "count".
     *
     * By default does nothing
     */
    protected function preCount()
    {
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function count()
    {
        $this->preCount();
        $result = $this->dataStore->count();
        $this->postCount($result);
        return $result;
    }

    /**
     * The post-aspect for "count"
     *
     * By default does nothing
     *
     * @param mixed $result
     */
    protected function postCount(&$result)
    {
    }
}