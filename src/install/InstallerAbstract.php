<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\install;

use Interop\Container\ContainerInterface;
use zaboy\res\Install\InstallerInterface;
use zaboy\rest\install\DataStore\Eav\Installer as EavInstaller;
use Zend\Db\Adapter\Adapter;

/**
 * Installer class
 *
 * @category   Zaboy
 * @package    zaboy
 */
abstract class InstallerAbstract implements InstallerInterface
{

    const PRODACTION = 'prod';
    const TESTING = 'test';

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Make clean and install.
     * @return void
     */
    public function reinstall()
    {
        $this->uninstall();
        $this->install();
    }
}
