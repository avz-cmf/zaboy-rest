<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.07.16
 * Time: 12:51
 */
namespace zaboy\rest\DataSource;

use Xiag\Rql\Parser\Query;
use zaboy\rest\DataStore\DbTable;
use zaboy\rest\DataStore\Interfaces\DataSourceInterface;

class DbTableDataSource extends DbTable implements DataSourceInterface
{

    /**
     * @return array Return data of DataSource
     */
    public function getAll()
    {
        return $this->query(new Query());
    }
}
