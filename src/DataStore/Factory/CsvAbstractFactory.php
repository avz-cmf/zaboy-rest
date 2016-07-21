<?php

namespace zaboy\rest\DataStore\Factory;

use Interop\Container\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use zaboy\rest\AbstractFactoryAbstract;
use zaboy\rest\DataStore\DataStoreException;

class CsvAbstractFactory extends AbstractDataStoreFactory
{

    static $KEY_DATASTORE_CLASS = 'zaboy\rest\DataStore\CsvBase';
    const KEY_FILENAME = 'filename';
    const KEY_DELIMITER = 'delimiter';


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
        if (!isset($serviceConfig[self::KEY_FILENAME])) {
            throw new DataStoreException(sprintf('The file name for "%s" is not specified in the config \'dataStore\'', $requestedName));
        }
        $filename = $serviceConfig[self::KEY_FILENAME];
        $delimiter = (isset($serviceConfig[self::KEY_DELIMITER]) ? $serviceConfig[self::KEY_DELIMITER] : null);
        $lockHandler = new LockHandler($filename);

        return new $requestedClassName($filename, $delimiter, $lockHandler);
    }

}