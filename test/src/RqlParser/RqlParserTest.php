<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 06.06.16
 * Time: 17:01
 */

namespace zaboy\test\res\RqlParser;

use PHPUnit_Framework_TestCase;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\InNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\OrNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\NeNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use zaboy\rest\RqlParser\AggregateFunctionNode;
use zaboy\rest\RqlParser\RqlParser;
use zaboy\rest\RqlParser\XSelectNode;

class RqlParserTest extends PHPUnit_Framework_TestCase
{
    /** @var  RqlParser */
    private $object;

    /** @var  Query */
    private $queryObject;
    private $rqlString;

    public function setUp()
    {
        $this->queryObject = new Query();

        $this->queryObject->setQuery(
            new AndNode([
                new AndNode([
                    new EqNode('q', null),
                    new NeNode('q', null),
                    new LeNode('q', 'r'),
                    new GeNode('q', 'u')
                ]),
                new OrNode([
                    new LtNode('q', 't'),
                    new GtNode('q', 'y'),
                    new InNode('q', ['a','s','d','f','g'])
                ])

            ])
        );

        $this->queryObject->setSelect(new XSelectNode([
            'q',
            (new AggregateFunctionNode('max', 'q')),
            (new AggregateFunctionNode('min', 'q')),
            (new AggregateFunctionNode('count', 'q')),
        ]));

        $this->queryObject->setSort(new SortNode(['q' => -1]));
        $this->queryObject->setLimit(new LimitNode(20, 30));

        $this->rqlString  = "and(and(eq(q,null()),ne(q,null()),le(q,r),ge(q,u)),or(lt(q,t),gt(q,y),in(q,(a,s,d,f,g))))";
        $this->rqlString .= "&limit(20,30)";
        $this->rqlString .= "&sort(-q)";
        $this->rqlString .= "&select(q,max(q),min(q),count(q))";
    }

    public function testRqlDecoder()
    {
        $queryObject = RqlParser::rqlDecode($this->rqlString);
        $this->assertTrue(isset($queryObject));
        $this->assertEquals($this->queryObject, $queryObject);
    }

    public function testRqlEncode()
    {
        $rqlString = RqlParser::rqlEncode($this->queryObject);
        $this->assertEquals($rqlString, $this->rqlString);
    }
}
