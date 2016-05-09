<?php
/**
 * Created by PhpStorm.
 * User: VIPrules
 * Date: 07.05.2016
 * Time: 16:50
 */

namespace zaboy\rest\DataStore\Factory;

use  zaboy\rest\DataStore\Factory\CsvBaseAbstractFactory;

class CsvIntIdAbstractFactory extends CsvBaseAbstractFactory
{
    protected $classOfEntity = 'zaboy\rest\DataStore\CsvIntId';
}