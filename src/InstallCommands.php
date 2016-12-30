<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.12.16
 * Time: 6:28 PM
 */

namespace zaboy\rest;

use Composer\Script\Event;
use zaboy\installer\Install\AbstractCommand;
use zaboy\installer\Install\InstallerInterface;

class InstallCommands extends AbstractCommand
{
    /**
     * @param null $dir
     * @return InstallerInterface[]
     */
    public static function getInstallers($dir = null)
    {
        if (!isset($dir)) {
            $dir = __DIR__;
        }
        return parent::getInstallers($dir);
    }

}