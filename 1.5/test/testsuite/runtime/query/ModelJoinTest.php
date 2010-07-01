<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';
require_once 'tools/helpers/bookstore/BookstoreDataPopulator.php';

/**
 * Test class for ModelJoin.
 *
 * @author     François Zaninotto
 * @version    $Id: ModelJoinTest.php 1347 2009-12-03 21:06:36Z francois $
 * @package    runtime.query
 */
class ModelJoinTest extends BookstoreTestBase 
{
	/**
	 * @expectedException PropelException
	 */  
	public function testTableMapNotSet()
	{
		$join = new ModelJoin();
		$this->assertNull($join->getTableMap(), 'getTableMap() throws an exception as long as no table map is set');
	}
	
	public function testTableMap()
	{
		$join = new ModelJoin();
		
	    $tmap = new TableMap();
		$tmap->foo = 'bar';
		
		$join->setTableMap($tmap);
		$this->assertEquals($tmap, $join->getTableMap(), 'getTableMap() returns the TableMap previously set by setTableMap()');
	}

	public function testSetRelationMap()
	{
		$c = new Criteria();
		$join = new ModelJoin();
		$this->assertNull($join->getRelationMap(), 'getRelationMap() returns null as long as no relation map is set');
		$bookTable = BookPeer::getTableMap();
		$relationMap = $bookTable->getRelation('Author');
		$join->setRelationMap($c, $relationMap);
		$this->assertEquals($relationMap, $join->getRelationMap(), 'getRelationMap() returns the RelationMap previously set by setRelationMap()');
	}
	
	public function testSetRelationMapDefinesJoinColumns()
	{
		$c = new Criteria();
		$bookTable = BookPeer::getTableMap();
		$join = new ModelJoin();
		$join->setTableMap($bookTable);
		$join->setRelationMap($c, $bookTable->getRelation('Author'));
		
		$joinCriterion = new Criterion($c, null, 'book.AUTHOR_ID=author.ID', Criteria::CUSTOM);
		$this->assertEquals($joinCriterion, $join->getCondition(), 'setRelationMap() automatically sets the JoinCondition');
	}

	public function testSetRelationMapLeftAlias()
	{
		$c = new Criteria();
		$bookTable = BookPeer::getTableMap();
		$join = new ModelJoin();
		$join->setTableMap($bookTable);
		$join->setRelationMap($c, $bookTable->getRelation('Author'), 'b');
		
		$joinCriterion = new Criterion($c, null, 'b.AUTHOR_ID=author.ID', Criteria::CUSTOM);
		$this->assertEquals($joinCriterion, $join->getCondition(), 'setRelationMap() automatically sets the JoinCondition');
	}

	public function testSetRelationMapRightAlias()
	{
		$c = new Criteria();
		$bookTable = BookPeer::getTableMap();
		$join = new ModelJoin();
		$join->setTableMap($bookTable);
		$join->setRelationMap($c, $bookTable->getRelation('Author'), null, 'a');
		
		$joinCriterion = new Criterion($c, null, 'book.AUTHOR_ID=a.ID', Criteria::CUSTOM);
		$this->assertEquals($joinCriterion, $join->getCondition(), 'setRelationMap() automatically sets the JoinCondition');
	}

	public function testSetRelationMapComposite()
	{
		$c = new Criteria();
		$table = ReaderFavoritePeer::getTableMap();
		$join = new ModelJoin();
		$join->setTableMap($table);
		$join->setRelationMap($c, $table->getRelation('BookOpinion'));
		
		$joinCriterion1 = new Criterion($c, null, ReaderFavoritePeer::BOOK_ID.'='.BookOpinionPeer::BOOK_ID, Criteria::CUSTOM);
		$joinCriterion2 = new Criterion($c, null, ReaderFavoritePeer::READER_ID.'='.BookOpinionPeer::READER_ID, Criteria::CUSTOM);
		$joinCriterion1->addAnd($joinCriterion2);
		$this->assertEquals($joinCriterion1, $join->getCondition(), 'setRelationMap() automatically sets the JoinCondition');
	}

}
