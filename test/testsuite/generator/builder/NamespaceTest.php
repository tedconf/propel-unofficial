<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework/TestCase.php';
require_once dirname(__FILE__) . '/../../../../runtime/lib/Propel.php';
set_include_path(get_include_path() . PATH_SEPARATOR . "fixtures/namespaced/build/classes");	

/**
 * Tests for Namespaces in generated classes class
 * Requires a build of the 'namespaced' fixture
 *
 * @version    $Revision$
 * @package    generator.builder
 */
class NamespaceTest extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		if (version_compare(PHP_VERSION, '5.3.0') < 0) {
			$this->markTestSkipped('Namespace support requires PHP 5.3');
		}
		parent::setUp();
		Propel::init('fixtures/namespaced/build/conf/bookstore_namespaced-conf.php');
	}

	protected function tearDown()
	{
		parent::tearDown();
		Propel::init('fixtures/bookstore/build/conf/bookstore-conf.php');
	}
	
	public function testInsert()
	{
		$book = new \Foo\Bar\NamespacedBook();
		$book->setTitle('foo');
		$book->save();
		$this->assertFalse($book->isNew());
		
		$publisher = new \Baz\NamespacedPublisher();
		$publisher->save();
		$this->assertFalse($publisher->isNew());
	}

	public function testUpdate()
	{
		$book = new \Foo\Bar\NamespacedBook();
		$book->setTitle('foo');
		$book->save();
		$book->setTitle('bar');
		$book->save();
		$this->assertFalse($book->isNew());
	}

	public function testRelate()
	{
		$author = new NamespacedAuthor();
		$book = new \Foo\Bar\NamespacedBook();
		$book->setNamespacedAuthor($author);
		$book->save();
		$this->assertFalse($book->isNew());
		$this->assertFalse($author->isNew());
		
		$author = new NamespacedAuthor();
		$book = new \Foo\Bar\NamespacedBook();
		$author->addNamespacedBook($book);
		$author->save();
		$this->assertFalse($book->isNew());
		$this->assertFalse($author->isNew());
		
		$publisher = new \Baz\NamespacedPublisher();
		$book = new \Foo\Bar\NamespacedBook();
		$book->setNamespacedPublisher($publisher);
		$book->save();
		$this->assertFalse($book->isNew());
		$this->assertFalse($publisher->isNew());
	}
	
	public function testBasicQuery()
	{
		\Foo\Bar\NamespacedBookQuery::create()->deleteAll();
		\Baz\NamespacedPublisherQuery::create()->deleteAll();
		$noNamespacedBook = \Foo\Bar\NamespacedBookQuery::create()->findOne();
		$this->assertNull($noNamespacedBook);
		$noPublihser = \Baz\NamespacedPublisherQuery::create()->findOne();
		$this->assertNull($noPublihser);
	}
	
	public function testFind()
	{
		\Foo\Bar\NamespacedBookQuery::create()->deleteAll();
		$book = new \Foo\Bar\NamespacedBook();
		$book->setTitle('War And Peace');
		$book->save();
		$book2 = \Foo\Bar\NamespacedBookQuery::create()->findPk($book->getId());
		$this->assertEquals($book, $book2);
		$book3 = \Foo\Bar\NamespacedBookQuery::create()->findOneByTitle($book->getTitle());
		$this->assertEquals($book, $book3);
	}

	public function testGetRelatedManyToOne()
	{
		\Foo\Bar\NamespacedBookQuery::create()->deleteAll();
		\Baz\NamespacedPublisherQuery::create()->deleteAll();
		$publisher = new \Baz\NamespacedPublisher();
		$book = new \Foo\Bar\NamespacedBook();
		$book->setNamespacedPublisher($publisher);
		$book->save();
		\Foo\Bar\NamespacedBookPeer::clearInstancePool();
		\Baz\NamespacedPublisherPeer::clearInstancePool();
		$book2 = \Foo\Bar\NamespacedBookQuery::create()->findPk($book->getId());
		$publisher2 = $book2->getNamespacedPublisher();
		$this->assertEquals($publisher->getId(), $publisher2->getId());
	}

	public function testGetRelatedOneToMany()
	{
		\Foo\Bar\NamespacedBookQuery::create()->deleteAll();
		\Baz\NamespacedPublisherQuery::create()->deleteAll();
		$author = new NamespacedAuthor();
		$book = new \Foo\Bar\NamespacedBook();
		$book->setNamespacedAuthor($author);
		$book->save();
		\Foo\Bar\NamespacedBookPeer::clearInstancePool();
		NamespacedAuthorPeer::clearInstancePool();
		$author2 = NamespacedAuthorQuery::create()->findPk($author->getId());
		$book2 = $author2->getNamespacedBooks()->getFirst();
		$this->assertEquals($book->getId(), $book2->getId());
	}
	
	public function testFindWithManyToOne()
	{
		\Foo\Bar\NamespacedBookQuery::create()->deleteAll();
		\Baz\NamespacedPublisherQuery::create()->deleteAll();
		$publisher = new \Baz\NamespacedPublisher();
		$book = new \Foo\Bar\NamespacedBook();
		$book->setNamespacedPublisher($publisher);
		$book->save();
		\Foo\Bar\NamespacedBookPeer::clearInstancePool();
		\Baz\NamespacedPublisherPeer::clearInstancePool();
		$book2 = \Foo\Bar\NamespacedBookQuery::create()
			->joinWith('NamespacedPublisher')
			->findPk($book->getId());
		$publisher2 = $book2->getNamespacedPublisher();
		$this->assertEquals($publisher->getId(), $publisher2->getId());
	}

	public function testFindWithOneToMany()
	{
		\Foo\Bar\NamespacedBookQuery::create()->deleteAll();
		NamespacedAuthorQuery::create()->deleteAll();
		$author = new NamespacedAuthor();
		$book = new \Foo\Bar\NamespacedBook();
		$book->setNamespacedAuthor($author);
		$book->save();
		\Foo\Bar\NamespacedBookPeer::clearInstancePool();
		NamespacedAuthorPeer::clearInstancePool();
		$author2 = NamespacedAuthorQuery::create()
			->joinWith('NamespacedBook')
			->findPk($author->getId());
		$book2 = $author2->getNamespacedBooks()->getFirst();
		$this->assertEquals($book->getId(), $book2->getId());
	}
	
	public function testSingleTableInheritance()
	{
		\Foo\Bar\NamespacedBookstoreEmployeeQuery::create()->deleteAll();
		$emp = new \Foo\Bar\NamespacedBookstoreEmployee();
		$emp->setName('Henry');
		$emp->save();
		$man = new \Foo\Bar\NamespacedBookstoreManager();
		$man->setName('John');
		$man->save();
		$cas = new \Foo\Bar\NamespacedBookstoreCashier();
		$cas->setName('William');
		$cas->save();
		$emps = \Foo\Bar\NamespacedBookstoreEmployeeQuery::create()
			->orderByName()
			->find();
		$this->assertEquals(3, count($emps));
		$this->assertTrue($emps[0] instanceof \Foo\Bar\NamespacedBookstoreEmployee);
		$this->assertTrue($emps[1] instanceof \Foo\Bar\NamespacedBookstoreManager);
		$this->assertTrue($emps[2] instanceof \Foo\Bar\NamespacedBookstoreCashier);
		$nbMan = \Foo\Bar\NamespacedBookstoreManagerQuery::create()
			->count();
		$this->assertEquals(1, $nbMan);
	}
	
}
