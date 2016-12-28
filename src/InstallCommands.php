<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.12.16
 * Time: 6:28 PM
 */

namespace zaboy\rest;


use Composer\Script\Event;
use zaboy\res\Install\AbstractCommand;
use zaboy\res\Install\InstallerInterface;
use zaboy\rest\install\DataStore\Composite\Installer as CompositeInstaller;
use zaboy\rest\install\DataStore\Eav\Installer as EavInstaller;

class InstallCommands extends AbstractCommand
{

    /**
     * return array with Install class for lib;
     * @return array
     */
    public static function getInstallers()
    {
        return InstallCommands::whoIAm() == "app" ? [
            CompositeInstaller::class,
            EavInstaller::class
        ] : [];
    }

    /**
     * @param Event $event
     */
    public static function install(Event $event)
    {
        parent::command($event, parent::INSTALL, self::getInstallers());
    }
    /**
     * @param Event $event
     */
    public static function uninstall(Event $event)
    {
        parent::command($event, parent::UNINSTALL, self::getInstallers());
    }
    /**
     * @param Event $event
     */
    public static function reinstall(Event $event)
    {
        parent::command($event, parent::REINSTALL, self::getInstallers());
    }
}