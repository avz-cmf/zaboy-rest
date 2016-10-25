<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\install;

use zaboy\rest\install\DataStore\Eav\Installer as EavInstaller;
use zaboy\rest\install\InstallerAbstract;
use zaboy\rest\install\DataStore\Eav\Example\StoreCatalog as EavExampleStoreCatalog;

/**
 * Installer class
 *
 * @category   Zaboy
 * @package    zaboy
 */
class Installer extends InstallerAbstract
{

    /**
     *
     * @var EavInstaller
     */
    protected $eavInstaller;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->dbAdapter = $this->container->get('db');
        $this->eavInstaller = new EavInstaller($container);
    }

    public function install()
    {
        $this->eavInstaller->install();
    }

    public function uninstall()
    {
        $this->eavInstaller->uninstall();
    }

    public function rewrite()
    {
        $this->eavInstaller->uninstall();
        $this->eavInstaller->install();
    }

    public function addDataEavExampleStoreCatalog()
    {
        $this->rewrite();
        $eavExampleStoreCatalog = new EavExampleStoreCatalog($this->container);
        $eavExampleStoreCatalog->addData();
    }

}
