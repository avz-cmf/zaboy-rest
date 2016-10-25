<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace zaboy\rest\DataStore\Eav;

use zaboy\rest\DataStore\DbTable;
use zaboy\rest\DataStore\DataStoreException;

/**
 *
 * Add to config:
 * <code>
 *     'dataStore' => [
 *         SysEntities::TABLE_NAME => [
 *             'class' => SysEntities::class,
 *             'tableName' => SysEntities::TABLE_NAME
 *         ],
 *     ],
 * </code>
 *
 * Table'sys_entities' must be exist. Use src\installer for create.
 *
 * @see http://www.cyberforum.ru/ms-access/thread1353090.html запрос
 */
class SysEntities extends DbTable
{

    const TABLE_NAME = 'sys_entities';
    const ENTITY_PREFIX = 'entity_';
    const PROP_PREFIX = 'prop_';
    const ID_SUFFIX = '_id';


    public function prepareEntityCreate($entityName, $itemData, $rewriteIfExist)
    {
        $identifier = $this->getIdentifier();
        //What is it array of arrays?
        if (isset($itemData[$identifier]) && $rewriteIfExist) {
            $this->delete($itemData[$identifier]);
        }
        $sysItem = [
            'add_date' => (new \DateTime())->format("Y-m-d"),
            'entity_type' => $entityName,
        ];
        if (isset($itemData[$identifier])) {
            $sysItem[$identifier] = $itemData[$identifier];
        }
        $sysItemInserted = $this->create($sysItem);
        if (empty($sysItemInserted)) {
            throw new DataStoreException('Can not insert record for ' . $entityName . 'to sys_entities');
        }
        $itemData[$identifier] = $sysItemInserted[$identifier];
        return $itemData;
    }

    public static function getEntityName($tableName)
    {
        $entityName = substr($tableName, strlen(SysEntities::ENTITY_PREFIX));
        return $entityName;
    }

    public static function getEntityTableName($entityName)
    {
        $tableName = SysEntities::ENTITY_PREFIX . $entityName;
        return $tableName;
    }

    public static function getPropName($tableName)
    {
        $propName = substr($tableName, strlen(SysEntities::PROP_PREFIX));
        return $propName;
    }

    public static function getPropTableName($propName)
    {
        $tableName = SysEntities::PROP_PREFIX . $propName;
        return $tableName;
    }

    public function deleteAllInEntity($entityType){
        $where = SysEntities::ENTITY_PREFIX . 'type = \'' . $entityType . '\'';
        $deletedItemsCount = $this->dbTable->delete($where);
        return $deletedItemsCount;
    }
}
