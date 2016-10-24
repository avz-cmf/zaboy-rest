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

    public function prepareEntityCreate($entityName, $itemData)
    {
        $identifier = $this->getIdentifier();
        //What is it array of arrays?
/*        if (isset($itemData[$identifier]) && $rewriteIfExist) {
            $sysEntities->delete($itemData[$identifier]);
        }*/
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

}
