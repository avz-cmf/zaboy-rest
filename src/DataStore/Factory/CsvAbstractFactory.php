<?php

namespace zaboy\rest\DataStore\Factory;

use Interop\Container\ContainerInterface;
use zaboy\rest\AbstractFactoryAbstract;
use zaboy\rest\DataStore\DataStoreException;

class CsvAbstractFactory extends AbstractFactoryAbstract
{
    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');
        if (!isset($config['dataStore'][$requestedName]['class'])) {
            return false;
        }
        $requestedClassName = $config['dataStore'][$requestedName]['class'];
        return is_a($requestedClassName, 'zaboy\rest\DataStore\CsvBase', true);
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $serviceConfig = $config['dataStore'][$requestedName];
        $requestedClassName = $serviceConfig['class'];
        if (!isset($serviceConfig['filename'])) {
            throw new DataStoreException(sprintf('The file name for "%s" is not specified in the config \'dataStore\'', $requestedName));
        }
        $filename = $serviceConfig['filename'];
        $delimiter = (isset($serviceConfig['delimiter']) ? $serviceConfig['delimiter'] : null);
        return new $requestedClassName($filename, $delimiter);
    }

}