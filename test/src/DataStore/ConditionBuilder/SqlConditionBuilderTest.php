<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 31.05.16
 * Time: 17:22
 */

namespace zaboy\test\res\DataStore\ConditionBuilder;

use Zend\Db\Adapter\AdapterInterface;
use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node;
use Xiag\Rql\Parser\QueryBuilder;
use zaboy\rest\DataStore\ConditionBuilder\SqlConditionBuilder;

class SqlConditionBuilderTest extends ConditionBuilderTest
{

    public function providerPrepareFildName()
    {
        return array(
            array('fildName', ''),
            array('FildName', ''),
            array('Fild_Name', ''),
        );
    }

    public function providerGetValueFromGlob()
    {

        return array(
            array('abc', ''),
            array('*abc', ''),
            array('abc*', ''),
            array('a*b?c', ''),
            array('?abc', ''),
            array('abc?', ''),
            array(rawurlencode('Шщ +-*._'), 'Шщ +-*._'),
        );
    }

    public function provider__invoke(){
        return array(
            array(null, ''),
            array(
                (new QueryBuilder())
                    ->addQuery(new Node\Query\ScalarOperator\EqNode('name', 'value'))
                    ->getQuery()->getQuery(),
                ''
            ),
            array(
                (new QueryBuilder())
                    ->addQuery(new Node\Query\ScalarOperator\EqNode('a', 1))
                    ->addQuery(new Node\Query\ScalarOperator\NeNode('b', 2))
                    ->addQuery(new Node\Query\ScalarOperator\LtNode('c', 3))
                    ->addQuery(new Node\Query\ScalarOperator\GtNode('d', 4))
                    ->addQuery(new Node\Query\ScalarOperator\LeNode('e', 5))
                    ->addQuery(new Node\Query\ScalarOperator\GeNode('f', 6))
                    ->addQuery(new Node\Query\ScalarOperator\LikeNode('g', new Glob('*abc?')))
                    ->getQuery()->getQuery(),
                ''
            ),
            array(
                (new QueryBuilder())
                    ->addQuery(new Node\Query\LogicOperator\AndNode([
                        new Node\Query\ScalarOperator\EqNode('a', 'b'),
                        new Node\Query\ScalarOperator\LtNode('c', 'd'),
                        new Node\Query\LogicOperator\OrNode([
                            new Node\Query\ScalarOperator\LtNode('g', 5),
                            new Node\Query\ScalarOperator\GtNode('g', 2),
                        ])
                    ]))
                    ->addQuery(new Node\Query\LogicOperator\NotNode([
                        new Node\Query\ScalarOperator\NeNode('h', 3),
                    ]))
                    ->getQuery()->getQuery(),
                ''
            ),
            array(
                (new QueryBuilder())
                    ->addQuery(new Node\Query\LogicOperator\AndNode([
                        new Node\Query\ScalarOperator\EqNode('a', '`NULL`'),
                        new Node\Query\ScalarOperator\LtNode('c', 'd'),
                        new Node\Query\LogicOperator\OrNode([
                            new Node\Query\ScalarOperator\LtNode('g', 5),
                            new Node\Query\ScalarOperator\GtNode('g', 2),
                        ])
                    ]))
                    ->addQuery(new Node\Query\LogicOperator\NotNode([
                        new Node\Query\ScalarOperator\NeNode('h', 3),
                    ]))
                    ->getQuery()->getQuery(),
                ''
            ),
        );
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->object = new SqlConditionBuilder($this->getMockBuilder('AdapterInterface')->getMock());
    }
}
