<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\install;

use zaboy\rest\install\DataStore\Eav\Installer as EavInstaller;
use Zend\Db\Adapter\Adapter;

/**
 * Installer class
 *
 * @category   Zaboy
 * @package    zaboy
 */
class InstallerAbstract
{

    const PRODACTION = 'prod';
    const TESTING = 'test';

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

}
