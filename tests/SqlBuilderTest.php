<?php

define('ROOT', dirname(__FILE__) . '/../');
require_once ROOT . 'lib/SongFinder.php';
require_once ROOT . 'lib/SqlBuilder.php';
require_once ROOT . 'lib/SqlBuilderWhereClause.php';

class SqlBuilderTest extends PHPUnit_Framework_TestCase
{    
    function test0()
    {
        $finder = new SongFinder();
        list($sql, $params) = $finder->createWhereClause('a', 'x=?');
        $this->assertEquals(1, count($params));
        $this->assertEquals('a', $params[0]);
        $this->assertEquals('x=?', $sql);
    }
    
    function test1()
    {
        $finder = new SongFinder();
        list($sql, $params) = $finder->createWhereClause('a && b', 'x=?');
        $this->assertEquals(2, count($params));
        $this->assertEquals('a', $params[0]);
        $this->assertEquals('b', $params[1]);
        $this->assertEquals('x=? and x=?', $sql);
    }
    
    function test2()
    {
        $finder = new SongFinder();
        list($sql, $params) = $finder->createWhereClause('a && b || c', 'x=?');
        $this->assertEquals(3, count($params));
        $this->assertEquals('a', $params[0]);
        $this->assertEquals('b', $params[1]);
        $this->assertEquals('c', $params[2]);
        $this->assertEquals('x=? and x=? or x=?', $sql);
    }
    
    function test3()
    {
        $finder = new SongFinder();
        list($sql, $params) = $finder->createWhereClause('(a && (b || c) && (d || e)) || !f', 'x=?');
        $this->assertEquals(6, count($params), var_export($params, true));
        $this->assertEquals('a', $params[0]);
        $this->assertEquals('b', $params[1]);
        $this->assertEquals('c', $params[2]);
        $this->assertEquals('d', $params[3]);
        $this->assertEquals('e', $params[4]);
        $this->assertEquals('f', $params[5]);
        $this->assertEquals('( x=? and ( x=? or x=? ) and ( x=? or x=? ) ) or not x=?', $sql);
    }
    
    function test4()
    {
        $finder = new SongFinder();
        list($sql, $params) = $finder->createWhereClause('a && !b', 'x=?');
        $this->assertEquals(2, count($params));
        $this->assertEquals('a', $params[0]);
        $this->assertEquals('b', $params[1]);
        $this->assertEquals('x=? and not x=?', $sql);
    }
    
    function testNextToken1()
    {
        $finder = new SongFinder();
        $this->assertEquals(array('&&', 0), $finder->_nextToken('&&', 0));
        $this->assertEquals(array('&&', 2), $res=$finder->_nextToken('a && b', 0), var_export($res, true));
        $this->assertEquals(array('||', 0), $finder->_nextToken('||', 0));
        $this->assertEquals(array('||', 2), $finder->_nextToken('a || b', 0));
        $this->assertEquals(array('||', 2), $finder->_nextToken('a || b || c', 0));
        $this->assertEquals(array('||', 7), $finder->_nextToken('a || b || c', 4));
        $this->assertEquals(array('&&', 2), $finder->_nextToken('a && b && c', 0));
        $this->assertEquals(array('&&', 7), $finder->_nextToken('a && b && c', 4));
        $this->assertEquals(array('||', 2), $finder->_nextToken('a || b && c', 0));
        $this->assertEquals(array('&&', 7), $finder->_nextToken('a || b && c', 4));
        $this->assertEquals(array('&&', 2), $finder->_nextToken('a && b || c', 0));
        $this->assertEquals(array('||', 7), $finder->_nextToken('a && b || c', 4));
        $this->assertEquals(false, $finder->_nextToken('a', 0));
        $this->assertEquals(false, $finder->_nextToken('a && b', 4));
        $this->assertEquals(false, $finder->_nextToken('a && b', 5));
        $this->assertEquals(false, $finder->_nextToken('a && b', 6));
        $this->assertEquals(false, $finder->_nextToken('a && b', 7));
    }
    
    function testParse1()
    {
        $finder = new SongFinder();
        $this->assertTrue($finder->_validTokens($finder->_tokenize('(css && !donkey)')));
        $this->assertTrue($finder->_validTokens($finder->_tokenize('(css && !donkey) || the beatles')));
        $this->assertTrue($finder->_validTokens($finder->_tokenize('css && the beatles && !donkey')));
    }
}