<?php

namespace zaboy\rest\DataStore\Aspect\Factory;

use Interop\Container\ContainerInterface;
use zaboy\rest\AbstractFactoryAbstract;
use zaboy\rest\DataStore\DataStoreException;
use zaboy\rest\DataStore\Factory\AbstractDataStoreFactory;

/**
 * Create and return an instance of the DataStore which based on AspectAbstract
 *
 * The configuration can contain:
 * <code>
 * 'DataStore' => [
 *
 *     'real_service_name_for_aspect_datastore' => [
 *         'class' => 'zaboy\rest\DataStore\Aspect\AspectAbstract',
 *         'dataStore' => 'real_service_name_of_any_type_of_datastore'  // this service must be exist
 *     ]
 * ]
 * </code>
 *
 * @category   rest
 * @package    zaboy
 */
class AspectAbstractFactory extends AbstractDataStoreFactory
{

    static $KEY_DATASTORE_CLASS = 'zaboy\rest\DataStore\Aspect\AspectAbstract';

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config[self::KEY_DATASTORE][$requestedName];
        $requestedClassName = $serviceConfig[self::KEY_CLASS];
        if (!isset($serviceConfig['dataStore'])) {
            throw new DataStoreException(sprintf('The dataStore type for "%s" is not specified in the config "'
                . self::KEY_DATASTORE . '"', $requestedName));
        }
        $dataStore = $container->get($serviceConfig['dataStore']);
        return new $requestedClassName($dataStore);
    }

}