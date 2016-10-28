<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\install;

use zaboy\rest\install\DataStore\Eav\Installer as EavInstaller;
use zaboy\rest\install\DataStore\Eav\Example\StoreCatalog as EavExampleStoreCatalog;
use zaboy\rest\install\DataStore\Composite\Installer as CompositeInstaller;
use zaboy\rest\install\DataStore\Composite\Example\Store as CompositeExampleStore;

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
    protected $compositeInstaller;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->dbAdapter = $this->container->get('db');
        $this->eavInstaller = new EavInstaller($container);
        $this->compositeInstaller = new CompositeInstaller($container);
    }

    public function install()
    {
        $this->eavInstaller->install();
        $this->compositeInstaller->install();
    }

    public function uninstall()
    {
        $this->eavInstaller->uninstall();
        $this->compositeInstaller->uninstall();
    }

    public function rewrite()
    {
        $this->eavInstaller->uninstall();
        $this->eavInstaller->install();
        $this->compositeInstaller->uninstall();
        $this->compositeInstaller->install();
    }

    public function addDataEavExampleStoreCatalog()
    {
        $this->rewrite();
        $eavExampleStoreCatalog = new EavExampleStoreCatalog($this->container);
        $eavExampleStoreCatalog->addData();
        $compositeExampleStore = new CompositeExampleStore($this->container);
        $compositeExampleStore->addData();
    }

}
