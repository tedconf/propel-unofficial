<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../../../tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Test for Nested Transactions.
 *
 * @package    runtime.connection
 * 
 */
class TransactionsTest extends PHPUnit_Framework_TestCase
{
	protected $pdo = null;
	
	public function tearDown()
	{
		//Always rollback, whatever the situation
		try {
			$this->pdo->forceRollBack();
		}
		catch (Exception $e) { }
	}
	
	public function testBeginTransaction()
	{
		$this->pdo = Propel::getConnection();
		$this->pdo->beginTransaction();
		$this->assertTrue($this->pdo->isInTransaction());
	}
	
	public function testRollbackNoQueries()
	{
		$this->pdo = Propel::getConnection();
		$this->pdo->beginTransaction();
		$this->assertTrue($this->pdo->isInTransaction());
		$this->pdo->rollBack();
		$this->assertFalse($this->pdo->isInTransaction());
	}
	
	public function testRollbackNoTransactionNoQueries()
	{
		$this->pdo = Propel::getConnection();
		$this->assertFalse($this->pdo->isInTransaction());
		$this->pdo->rollBack();
		$this->assertFalse($this->pdo->isInTransaction());
	}
	
	public function testNestedTransactionsBeginTransaction()
	{
		$this->pdo = Propel::getConnection();
		$this->assertFalse($this->pdo->isInTransaction());
		$this->pdo->beginTransaction();
		$this->assertTrue($this->pdo->isInTransaction());
		$this->assertEquals(1, $this->pdo->getNestedTransactionCount());
		$this->pdo->beginTransaction();
		$this->assertTrue($this->pdo->isInTransaction());
		$this->assertEquals(2, $this->pdo->getNestedTransactionCount());
	}
	
	public function testNestedTransactionsRollBackNoQueries()
	{
		$this->pdo = Propel::getConnection();
		$this->assertFalse($this->pdo->isInTransaction());
		$this->pdo->beginTransaction();
		$this->assertTrue($this->pdo->isInTransaction());
		$this->assertEquals(1, $this->pdo->getNestedTransactionCount());
		$this->pdo->beginTransaction();
		$this->assertTrue($this->pdo->isInTransaction());
		$this->assertEquals(2, $this->pdo->getNestedTransactionCount());
		$this->pdo->rollBack();
		$this->assertTrue($this->pdo->isInTransaction());
		$this->assertEquals(1, $this->pdo->getNestedTransactionCount());
		$this->pdo->rollBack();
		$this->assertFalse($this->pdo->isInTransaction());
		$this->assertEquals(0, $this->pdo->getNestedTransactionCount());
	}
	
	public function testNestedTransactionsForceRollbackNoQueries()
	{
		$this->pdo = Propel::getConnection();
		$this->assertFalse($this->pdo->isInTransaction());
		$this->pdo->beginTransaction();
		$this->assertTrue($this->pdo->isInTransaction());
		$this->assertEquals(1, $this->pdo->getNestedTransactionCount());
		$this->pdo->beginTransaction();
		$this->assertTrue($this->pdo->isInTransaction());
		$this->assertEquals(2, $this->pdo->getNestedTransactionCount());
		$this->pdo->forceRollBack();
		$this->assertFalse($this->pdo->isInTransaction());
		$this->assertEquals(0, $this->pdo->getNestedTransactionCount());
	}
	
	public function testInsertRollback()
	{
		$this->pdo = Propel::getConnection();
		$this->pdo->beginTransaction();
		$author = new Author();
		$author->setFirstName('Arto');
		$author->setLastName('Paasilinna');
		$author->save();
		$id = $author->getId();
		
		AuthorPeer::clearInstancePool();
		$author = AuthorQuery::create()->findPk($id);
		$this->assertType('Author', $author);
		$this->assertEquals('Arto', $author->getFirstName());
		
		AuthorPeer::clearInstancePool();
		$this->pdo->rollBack();
		
		AuthorPeer::clearInstancePool();
		$author = AuthorQuery::create()->findPk($id);
		$this->assertNull($author);
	}
	
	public function testNestedTransactionsInsertRollback()
	{
		$this->pdo = Propel::getConnection();
		$this->pdo->beginTransaction(); // COUNT: 1
		
		$author1 = new Author();
		$author1->setFirstName('Arto');
		$author1->setLastName('Paasilinna');
		$author1->save();
		$id = $author1->getId();
		
		$this->pdo->beginTransaction(); // COUNT: 2
		
		$author2 = new Author();
		$author2->setFirstName('Kjell');
		$author2->setLastName('Westö');
		$author2->save();
		$id2 = $author2->getId();
		
		AuthorPeer::clearInstancePool();
		$author1 = AuthorQuery::create()->findPk($id);
		$author2 = AuthorQuery::create()->findPk($id2);
		$this->assertType('Author', $author1);
		$this->assertType('Author', $author2);
		$this->assertEquals('Arto', $author1->getFirstName());
		$this->assertEquals('Kjell', $author2->getFirstName());
		
		$this->pdo->rollBack(); // COUNT: 1
		AuthorPeer::clearInstancePool();
		
		$author1 = AuthorQuery::create()->findPk($id);
		$this->assertNull($author1);
		$author2 = AuthorQuery::create()->findPk($id2);
		$this->assertType('Author', $author2);
		$this->assertEquals('Kjell', $author2->getFirstName());
		
		$this->pdo->rollBack(); // COUNT: 0
		AuthorPeer::clearInstancePool();
		
		$author1 = AuthorQuery::create()->findPk($id);
		$author2 = AuthorQuery::create()->findPk($id2);
		$this->assertNull($author1);
		$this->assertNull($author2);
	}
}