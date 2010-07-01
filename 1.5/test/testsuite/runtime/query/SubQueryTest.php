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
 * Test class for SubQueryTest.
 *
 * @author     Francois Zaninotto
 * @version    $Id:  $
 * @package    runtime.query
 */
class SubQueryTest extends BookstoreTestBase
{
	protected function assertCriteriaTranslation($criteria, $expectedSql, $expectedParams, $message = '')
	{
		$params = array();
		$result = BasePeer::createSelectSql($criteria, $params);
		
		$this->assertEquals($expectedSql, $result, $message);
		$this->assertEquals($expectedParams, $params, $message);
	}
		
	public function testSubQuery()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		BookstoreDataPopulator::depopulate($con);
		BookstoreDataPopulator::populate($con);
		
		$subCriteria = new BookQuery();
		BookPeer::addSelectColumns($subCriteria);
		$subCriteria->orderByTitle(Criteria::ASC);
		
		$c = new BookQuery();
		BookPeer::addSelectColumns($c, 'subCriteriaAlias');
		$c->addSelectQuery($subCriteria, 'subCriteriaAlias');
		$c->groupBy('subCriteriaAlias.AuthorId');
		
		$sql = "SELECT subCriteriaAlias.ID, subCriteriaAlias.TITLE, subCriteriaAlias.ISBN, subCriteriaAlias.PRICE, subCriteriaAlias.PUBLISHER_ID, subCriteriaAlias.AUTHOR_ID FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` ORDER BY book.TITLE ASC) AS subCriteriaAlias GROUP BY subCriteriaAlias.AUTHOR_ID";
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'addSubQueryCriteriaInFrom() combines two queries succesfully');    
	}
	
	public function testSubQueryParameters()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		BookstoreDataPopulator::depopulate($con);
		BookstoreDataPopulator::populate($con);
		
		$authorRowling = AuthorQuery::create()->filterByLastName('Rowling')->findOne();
		
		$subCriteria = new BookQuery();
		$subCriteria->addSelfSelectColumns();
		$subCriteria->filterByAuthor($authorRowling);
		
		$c = new BookQuery();
		$c->addSelectQuery($subCriteria, 'subCriteriaAlias', true);
		// and use filterByPrice method!
		$c->filterByPrice(20, Criteria::LESS_THAN);
		
		$sql = "SELECT subCriteriaAlias.ID, subCriteriaAlias.TITLE, subCriteriaAlias.ISBN, subCriteriaAlias.PRICE, subCriteriaAlias.PUBLISHER_ID, subCriteriaAlias.AUTHOR_ID FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.AUTHOR_ID=:p2) AS subCriteriaAlias WHERE subCriteriaAlias.PRICE<:p1";
		$params = array(
			array('table' => 'book', 'column' => 'PRICE', 'value' => 20),
			array('table' => 'book', 'column' => 'AUTHOR_ID', 'value' => $authorRowling->getId()),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'addSubQueryCriteriaInFrom() combines two queries succesfully');    
	}

	public function testSubQueryRecursive()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		BookstoreDataPopulator::depopulate($con);
		BookstoreDataPopulator::populate($con);
		
		$publisher = PublisherQuery::create()->filterByName('Scholastic')->findOne();
		
		// sort the books (on date, if equal continue with id), filtered by a publisher
		 $sortedBookQuery = new BookQuery();
		 $sortedBookQuery->addSelfSelectColumns();
		 $sortedBookQuery->filterByPublisher($publisher);
		 $sortedBookQuery->orderByTitle(Criteria::DESC);
		 $sortedBookQuery->orderById(Criteria::DESC);

		 // group by author, after sorting!
		 $latestBookQuery = new BookQuery();
		 $latestBookQuery->addSelectQuery($sortedBookQuery, 'sortedBookQuery', true);
		 $latestBookQuery->groupBy('sortedBookQuery.AuthorId');

		 // filter from these latest books, find the ones cheaper than 12 euro
		 $c = new BookQuery();
		 $c->addSelectQuery($latestBookQuery, 'latestBookQuery', true);
		 $c->filterByPrice(12, Criteria::LESS_THAN);
		
		$sql = "SELECT latestBookQuery.ID, latestBookQuery.TITLE, latestBookQuery.ISBN, latestBookQuery.PRICE, latestBookQuery.PUBLISHER_ID, latestBookQuery.AUTHOR_ID ".
		 "FROM (SELECT sortedBookQuery.ID, sortedBookQuery.TITLE, sortedBookQuery.ISBN, sortedBookQuery.PRICE, sortedBookQuery.PUBLISHER_ID, sortedBookQuery.AUTHOR_ID ".
		 "FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID ".
		 "FROM `book` ".
		 "WHERE book.PUBLISHER_ID=:p2 ".
		 "ORDER BY book.TITLE DESC,book.ID DESC) AS sortedBookQuery ".
		 "GROUP BY sortedBookQuery.AUTHOR_ID) AS latestBookQuery ".
		 "WHERE latestBookQuery.PRICE<:p1";
		$params = array(
			array('table' => 'book', 'column' => 'PRICE', 'value' => 12),
			array('table' => 'book', 'column' => 'PUBLISHER_ID', 'value' => $publisher->getId()),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'addSubQueryCriteriaInFrom() combines two queries succesfully');    
	}
	
}
