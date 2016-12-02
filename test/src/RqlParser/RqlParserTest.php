<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 06.06.16
 * Time: 17:01
 */

namespace zaboy\test\rest\RqlParser;

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
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;
use zaboy\rest\Rql\Node\AggregateFunctionNode;
use zaboy\rest\Rql\Node\SelectNode;
use zaboy\rest\Rql\RqlParser;

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
                    new InNode('q', ['a', 's', 'd', 'f', 'g'])
                ])

            ])
        );

        $this->queryObject->setSelect(new SelectNode([
            'q',
            (new AggregateFunctionNode('max', 'q')),
            (new AggregateFunctionNode('min', 'q')),
            (new AggregateFunctionNode('count', 'q')),
        ]));

        $this->queryObject->setSort(new SortNode(['q' => -1, 'w' => 1, 'e' => 1]));
        $this->queryObject->setLimit(new LimitNode(20, 30));

        $this->rqlString = "and(and(eq(q,null()),ne(q,null()),le(q,r),ge(q,u)),or(lt(q,t),gt(q,y),in(q,(a,s,d,f,g))))";
        $this->rqlString .= "&limit(20,30)";
        $this->rqlString .= "&sort(-q,+w,e)";
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
        $this->rqlString = "and(and(eq(q,null()),ne(q,null()),le(q,r),ge(q,u)),or(lt(q,t),gt(q,y),in(q,(a,s,d,f,g))))";
        $this->rqlString .= "&limit(20,30)";
        $this->rqlString .= "&sort(-q,+w,+e)";
        $this->rqlString .= "&select(q,max(q),min(q),count(q))";

        $rqlString = RqlParser::rqlEncode($this->queryObject);
        $this->assertEquals($rqlString, $this->rqlString);
    }

    public function test__preparingQuery__oneNode()
    {
        $rqlString = RqlParser::rqlDecode("eq(email,aaa@gmail.com)");
        $query = new Query();
        $query->setQuery(new EqNode('email', 'aaa@gmail.com'));


        $this->assertEquals($query, $rqlString);
    }
    public function test__preparingQuery__inNode()
    {
        $rqlString = RqlParser::rqlDecode("in(email,(aaa@gmail.com,qwe,zxc))");
        $query = new Query();
        $query->setQuery(new InNode('email', ['aaa@gmail.com', 'qwe', 'zxc']));


        $this->assertEquals($query, $rqlString);
    }

    public function test__preparingQuery__insertedQuery()
    {
        $rqlString = RqlParser::rqlDecode('and(eq(email,aaa@gmail.com),or(le(age,1\,4),ge(age,1\.8)),ne(name,q1$3))');
        $query = new Query();
        $query->setQuery(new AndNode([
            new EqNode('email', 'aaa@gmail.com'),
            new OrNode([
                new LeNode('age', '1,4'),
                new GeNode('age', '1.8'),
            ]),
            new NeNode('name', 'q1$3'),
        ]));

        $this->assertEquals($query, $rqlString);
    }

    public function test__preparingQuery__withSelect()
    {
        $rqlString = RqlParser::rqlDecode("eq(email,aaa@gmail.com)&select(name,age,email)");
        $query = new Query();
        $query->setQuery(new EqNode('email', 'aaa@gmail.com'));
        $query->setSelect(new SelectNode(['name', 'age', 'email']));

        $this->assertEquals($query, $rqlString);
    }

    public function test__preparingQuery__fullQuery()
    {
        $rqlString = RqlParser::rqlDecode("eq(email,aaa@gmail.com)&limit(10,15)&sort(-name)&select(name,age,email)");
        $query = new Query();
        $query->setQuery(new EqNode('email', 'aaa@gmail.com'));
        $query->setSelect(new SelectNode(['name', 'age', 'email']));
        $query->setLimit(new LimitNode(10, 15));
        $query->setSort(new SortNode(['name'=> -1]));

        $this->assertEquals($query, $rqlString);
    }
}
