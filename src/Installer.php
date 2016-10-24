<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy;

error_reporting(E_ALL);
// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
$container = include 'config/container.php';

use zaboy\rest\install\DataStore\Eav\Installer as EavInstaller;

/**
 * Installer class
 *
 * @category   Zaboy
 * @package    zaboy
 */
class Installer
{

    const PRODACTION = 'prod';
    const TESTING = 'test';

    public static function install()
    {
        global $container;
        $dbAdapter = $container->get('db');
        $scriptInstaller = new EavInstaller($dbAdapter);
        $scriptInstaller->uninstall();
        $scriptInstaller->install();
    }

}

Installer::install();


