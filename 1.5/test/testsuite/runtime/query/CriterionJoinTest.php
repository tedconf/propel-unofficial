<?php
/**
	* This file is part of the Propel package.
	* For the full copyright and license information, please view the LICENSE
	* file that was distributed with this source code.
	*
	* @license    MIT License
	*/

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

/**
	* Tests the BasePeer classes.
	*
	* @see        BookstoreDataPopulator
	* @author     Hans Lellelid <hans@xmpl.org>
	* @package    runtime.util
	*/
class CriterionJoinTest extends BookstoreTestBase
{

	public function testCriterionJoinSimple()
	{
		$c = new Criteria(BookPeer::DATABASE_NAME);
		
		$criterion = $c->getNewCriterion(BookPeer::AUTHOR_ID, BookPeer::AUTHOR_ID . ' = ' . AuthorPeer::ID, Criteria::CUSTOM);
		
		$join = new CriterionJoin();
		$join->setLeftTableName(BookPeer::TABLE_NAME); 
		$join->setRightTableName(AuthorPeer::TABLE_NAME);
		$join->setCondition($criterion);
		
		// add join
		$c->addJoinObject($join);
		// add constraint in where (not on join)
		$c->add(AuthorPeer::ID, 12);
		
		BookPeer::addSelectColumns($c);
		
		$params = array();
		$expectedSql = 'SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` INNER JOIN author ON (book.AUTHOR_ID = author.ID) WHERE author.ID=:p1';
		$sql = BasePeer::createSelectSql($c, $params);
		$this->assertEquals($expectedSql, $sql);
	}

	public function testCriterionJoinComplex()
	{
		$c = new Criteria(BookPeer::DATABASE_NAME);
		
		$c1 = $c->getNewCriterion(BookPeer::AUTHOR_ID, BookPeer::AUTHOR_ID . ' = ' . AuthorPeer::ID, Criteria::CUSTOM);
		$c2 = $c->getNewCriterion(BookPeer::ID, BookPeer::ID . ' = ' . AuthorPeer::ID, Criteria::CUSTOM);
		$c2->addOr($c1);
		
		$join = new CriterionJoin();
		$join->setLeftTableName(BookPeer::TABLE_NAME); 
		$join->setRightTableName(AuthorPeer::TABLE_NAME);
		$join->setCondition($c2);   
		
		$c->addJoinObject($join);
		$c->add(AuthorPeer::ID, 12);
		
		$params = array();
		$expectedSql = 'SELECT  FROM  INNER JOIN author ON ((book.ID = author.ID OR book.AUTHOR_ID = author.ID)) WHERE author.ID=:p1';
		$sql = BasePeer::createSelectSql($c, $params);
		$this->assertEquals($expectedSql, $sql);
	}

	public function testCriterionJoinParameter()
	{
		$c = new Criteria(BookPeer::DATABASE_NAME);
		
		$c1 = $c->getNewCriterion(BookPeer::AUTHOR_ID, BookPeer::AUTHOR_ID . ' = ' . AuthorPeer::ID, Criteria::CUSTOM);
		$c2 = $c->getNewCriterion(BookPeer::TITLE, '%War%', Criteria::LIKE);
		$c1->addOr($c2);
		
		$join = new CriterionJoin();
		$join->setLeftTableName(BookPeer::TABLE_NAME); 
		$join->setRightTableName(AuthorPeer::TABLE_NAME);
		$join->setCondition($c1);
		
		$c->addJoinObject($join);
		$c->add(AuthorPeer::ID, 12);
		
		$params = array();
		$expectedSql = 'SELECT  FROM  INNER JOIN author ON ((book.AUTHOR_ID = author.ID OR book.TITLE LIKE :p2)) WHERE author.ID=:p1';
		$sql = BasePeer::createSelectSql($c, $params);
		$this->assertEquals($expectedSql, $sql);
	}

	public function testCriterionJoinRightAlias()
	{
		$c = new Criteria(BookPeer::DATABASE_NAME);
		$criterion = $c->getNewCriterion(BookPeer::AUTHOR_ID, BookPeer::AUTHOR_ID . ' = a.ID', Criteria::CUSTOM);
		
		$join = new CriterionJoin();
		$join->setLeftTableName(BookPeer::TABLE_NAME); 
		$join->setRightTableName(AuthorPeer::TABLE_NAME);
		$join->setRightTableAlias('a');
		$join->setCondition($criterion);
		
		$c->addJoinObject($join);
		
		$params = array();
		$expectedSql = 'SELECT  FROM `book` INNER JOIN author a ON (book.AUTHOR_ID = a.ID)';
		$sql = BasePeer::createSelectSql($c, $params);
		$this->assertEquals($expectedSql, $sql);
	}

}