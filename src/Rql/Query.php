<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 26.11.16
 * Time: 11:43 AM
 */

namespace zaboy\rest\Rql;

use Xiag\Rql\Parser\Query as StdQuery;

class Query extends StdQuery
{
    /**
     * Query constructor. Init query with rql string or another query obj.
     * @param $query
     */
    public function __construct($query)
    {
        if (is_string($query)) {
            /** @var Query $query */
            $query = RqlParser::rqlDecode($query);
        }
        if ($query instanceof Query) {
            $this->query = $query->query;
            $this->sort = $query->sort;
            $this->limit = $query->limit;
            $this->select = $query->select;
        }
    }
}