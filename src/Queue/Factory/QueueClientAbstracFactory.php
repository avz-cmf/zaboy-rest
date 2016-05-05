<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\Queue\Factory;

use Interop\Container\ContainerInterface;
use zaboy\rest\AbstractFactoryAbstract;
use zaboy\rest\RestException;
use zaboy\rest\Queue\Adapter;

/**
 * Create and return an instance of the array in Memory
 *
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 * 'queues' => [
 *     'MainQueue' => [
 *         'class' => 'zaboy\rest\Queue\QueueClient',
 *         'queuesDataStore' => QueuesDataStoreDbTable,
 *         'messagesDataStore' => MessagesDataStoreMemory,
 * ],
 * 'dataStore' => [
 *     'QueuesDataStoreDbTable' => [
 *         'class' => 'zaboy\rest\DataStore\DbTable',
 *         'tableName' => 'test_queues_tablle'
 *     ],
 *     'MessagesDataStoreMemory' => [
 *         'class' => 'zaboy\rest\DataStore\Memory'
 *     ],
 * ]
 * </code>
 *
 * @category   rest
 * @package    zaboy
 */
class QueueClientAbstracFactory extends AbstractFactoryAbstract
{

    const DEFAULT_MESSAGES_DATA_STORE = 'MessagesDataStore';
    const DEFAULT_QUEUES_DATA_STORE = 'QueuesDataStore';

    /**
     * Create and return an instance of the QueueClient.
     *
     *
     * @param  Interop\Container\ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return \DataStores\Interfaces\DataStoresInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config['queues'][$requestedName];
        $requestedClassName = $serviceConfig['class'];
        $queuesDataStore = $container->get($serviceConfig['queuesDataStore']);
        $messagesDataStore = $container->get($serviceConfig['messagesDataStore']);
        if (empty($queuesDataStore) || empty($messagesDataStore)) {
            throw new RestException('Can not load queuesDataStore or messagesDataStore');
        }
        $adapter = new Adapter\DataStores($queuesDataStore, $messagesDataStore);
        return new $requestedClassName($adapter);
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');
        if (!isset($config['queues'][$requestedName]['class'])) {
            return false;
        }
        $requestedClassName = $config['queues'][$requestedName]['class'];
        return is_a($requestedClassName, 'zaboy\rest\Queue\DataStoreQueueClient', true);
    }

}
