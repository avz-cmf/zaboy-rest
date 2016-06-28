<?php

namespace zaboy\rest\DataStore\Aspect\Factory;

use Interop\Container\ContainerInterface;
use zaboy\rest\AbstractFactoryAbstract;
use zaboy\rest\DataStore\DataStoreException;

class AspectDataStoreFactory extends AbstractFactoryAbstract
{
    const KEY_ASPECT = 'aspects';

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');
        if (!isset($config[self::KEY_ASPECT][$requestedName][self::KEY_CLASS])) {
            return false;
        }
        $requestedClassName = $config[self::KEY_ASPECT][$requestedName]['class'];
        return is_a($requestedClassName, 'zaboy\rest\DataStore\Aspect\AspectDataStore', true);
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config[self::KEY_ASPECT][$requestedName];
        $requestedClassName = $serviceConfig[self::KEY_CLASS];
        if (!isset($serviceConfig['dataStore'])) {
            throw new DataStoreException(sprintf('The dataStore type for "%s" is not specified in the config "'
                . self::KEY_ASPECT . '"', $requestedName));
        }
        $dataStore = $container->get($serviceConfig['dataStore']);
        return new $requestedClassName($dataStore);
    }

}