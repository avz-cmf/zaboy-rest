<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.07.16
 * Time: 11:44
 */

namespace zaboy\rest\DataStore\Interfaces;

interface DataSourceInterface
{

    /**
     * @return array Return data of DataSource
     */
    public function getData();
}
