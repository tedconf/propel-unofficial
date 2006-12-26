<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'bookstore/BookstoreTestBase.php';

/**
 * Tests the generated Peer classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various
 * peer operations.
 *
 * The database is relaoded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see BookstoreDataPopulator
 * @author Hans Lellelid <hans@xmpl.org>
 */
class GeneratedPeerTest extends BookstoreTestBase {

	/**
	 * Test ability to delete multiple rows via single Criteria object.
	 */
	public function t3estDoDelete_MultiTable() {

		$selc = new Criteria();
		$selc->add(BookPeer::TITLE, "Harry Potter and the Order of the Phoenix");
		$hp = BookPeer::doSelectOne($selc);

		// print "Attempting to delete [multi-table] by found pk: ";
		$c = new Criteria();
		$c->add(BookPeer::ID, $hp->getId());
		// The only way for multi-delete to work currently
		// is to specify the author_id and publisher_id (i.e. the fkeys
		// have to be in the criteria).
		$c->add(AuthorPeer::ID, $hp->getAuthorId());
		$c->add(PublisherPeer::ID, $hp->getPublisherId());
		$c->setSingleRecord(true);
		BookPeer::doDelete($c);

		//print_r(AuthorPeer::doSelect(new Criteria()));

		// check to make sure the right # of records was removed
		$this->assertEquals(3, count(AuthorPeer::doSelect(new Criteria())), "Expected 3 authors after deleting.");
		$this->assertEquals(3, count(PublisherPeer::doSelect(new Criteria())), "Expected 3 publishers after deleting.");
		$this->assertEquals(3, count(BookPeer::doSelect(new Criteria())), "Expected 3 books after deleting.");
	}

	/**
	 * Test using a complex criteria to delete multiple rows from a single table.
	 */
	public function testDoDelete_ComplexCriteria() {

		//print "Attempting to delete books by complex criteria: ";
		$c = new Criteria();
		$cn = $c->getNewCriterion(BookPeer::ISBN, "043935806X");
		$cn->addOr($c->getNewCriterion(BookPeer::ISBN, "0380977427"));
		$cn->addOr($c->getNewCriterion(BookPeer::ISBN, "0140422161"));
		$c->add($cn);
		BookPeer::doDelete($c);

		// now there should only be one book left; "The Tin Drum"

		$books = BookPeer::doSelect(new Criteria());

		$this->assertEquals(1, count($books), "Expected 1 book remaining after deleting.");
		$this->assertEquals("The Tin Drum", $books[0]->getTitle(), "Expect the only remaining book to be 'The Tin Drum'");
	}

	/**
	 * Test that cascading deletes are happening correctly (whether emulated or native).
	 */
	public function testDoDelete_Cascade() {

		// The 'media' table will cascade from book deletes

		// 1) Assert the row exists right now

			$medias = MediaPeer::doSelect(new Criteria());
			$this->assertTrue(count($medias) > 0, "Expected to find at least one row in 'media' table.");
			$media = $medias[0];
			$mediaId = $media->getId();

		// 2) Delete the owning book

			$owningBookId = $media->getBookId();
			BookPeer::doDelete($owningBookId);

		// 3) Assert that the media row is now also gone

			$obj = MediaPeer::retrieveByPK($mediaId);
			$this->assertNull($obj, "Expect NULL when retrieving on no matching Media.");

	}

	/**
	 * Test that onDelete="SETNULL" is happening correctly (whether emulated or native).
	 */
	public function testDoDelete_SetNull() {

		// The 'author_id' column in 'book' table will be set to null when author is deleted.

		// 1) Get an arbitrary book

			$c = new Criteria();
			$book = BookPeer::doSelectOne($c);
			$bookId = $book->getId();
			$authorId = $book->getAuthorId();
			unset($book);

		// 2) Delete the author for that book
			AuthorPeer::doDelete($authorId);

		// 3) Assert that the book.author_id column is now NULL

			$book = BookPeer::retrieveByPK($bookId);
			$this->assertNull($book->getAuthorId(), "Expect the book.author_id to be NULL after the author was removed.");

	}

	/**
	 * Test deleting a row by passing in the primary key to the doDelete() method.
	 */
	public function testDoDelete_ByPK() {

		// 1) get an arbitrary book
			$book = BookPeer::doSelectOne(new Criteria());
			$bookId = $book->getId();

		// 2) now delete that book
			BookPeer::doDelete($bookId);

		// 3) now make sure it's gone
			$obj = BookPeer::retrieveByPK($bookId);
			$this->assertNull($obj, "Expect NULL when retrieving on no matching Book.");

	}

	/**
	 * Test deleting a row by passing the generated object to doDelete().
	 */
	public function testDoDelete_ByObj() {

		// 1) get an arbitrary book
			$book = BookPeer::doSelectOne(new Criteria());
			$bookId = $book->getId();

		// 2) now delete that book
			BookPeer::doDelete($book);

		// 3) now make sure it's gone
			$obj = BookPeer::retrieveByPK($bookId);
			$this->assertNull($obj, "Expect NULL when retrieving on no matching Book.");

		}


	/**
	 * Test the doDeleteAll() method for single table.
	 */
	public function testDoDeleteAll() {

		BookPeer::doDeleteAll();
		$this->assertEquals(0, count(BookPeer::doSelect(new Criteria())), "Expect all book rows to have been deleted.");
	}

	/**
	 * Test the doDeleteAll() method when onDelete="CASCADE".
	 */
	public function testDoDeleteAll_Cascade() {

		BookPeer::doDeleteAll();
		$this->assertEquals(0, count(MediaPeer::doSelect(new Criteria())), "Expect all media rows to have been cascade deleted.");
		$this->assertEquals(0, count(ReviewPeer::doSelect(new Criteria())), "Expect all review rows to have been cascade deleted.");
	}

	/**
	 * Test the doDeleteAll() method when onDelete="SETNULL".
	 */
	public function testDoDeleteAll_SetNull() {

		$c = new Criteria();
		$c->add(BookPeer::AUTHOR_ID, null, Criteria::NOT_EQUAL);

		// 1) make sure there are some books with valid authors
		$this->assertTrue(count(BookPeer::doSelect($c)) > 0, "Expect some book.author_id columns that are not NULL.");

		// 2) delete all the authors
		AuthorPeer::doDeleteAll();

		// 3) now verify that the book.author_id columns are all nul
		$this->assertEquals(0, count(BookPeer::doSelect($c)), "Expect all book.author_id columns to be NULL.");
	}

	/**
	 * Test the doInsert() method when passed a Criteria object.
	 */
	public function testDoInsert_Criteria() {

		$name = "A Sample Publisher - " . time();

		$values = new Criteria();
		$values->add(PublisherPeer::ID, 1);
		$values->add(PublisherPeer::NAME, $name);
		PublisherPeer::doInsert($values);

		$c = new Criteria();
		$c->add(PublisherPeer::NAME, $name);

		$matches = PublisherPeer::doSelect($c);
		$this->assertEquals(1, count($matches), "Expect there to be exactly 1 publisher just-inserted.");
		$this->assertTrue( 1 != $matches[0]->getId(), "Expected to have different ID than one put in values Criteria.");

	}

	/**
	 * Test the doInsert() method when passed a generated object.
	 */
	public function testDoInsert_Obj() {

		$name = "A Sample Publisher - " . time();

		$values = new Publisher();
		$values->setName($name);
		PublisherPeer::doInsert($values);

		$c = new Criteria();
		$c->add(PublisherPeer::NAME, $name);

		$matches = PublisherPeer::doSelect($c);
		$this->assertEquals(1, count($matches), "Expect there to be exactly 1 publisher just-inserted.");
		$this->assertTrue( 1 != $matches[0]->getId(), "Expected to have different ID than one put in values Criteria.");

	}

	/**
	 * Tests performing doSelect() and doSelectJoin() using LIMITs.
	 */
	public function testDoSelect_Limit() {

		// 1) get the total number of items in a particular table
		$count = BookPeer::doCount(new Criteria());

		$this->assertTrue($count > 1, "Need more than 1 record in books table to perform this test.");

		$limitcount = $count - 1;

		$lc = new Criteria();
		$lc->setLimit($limitcount);

		$results = BookPeer::doSelect($lc);

		$this->assertEquals($limitcount, count($results), "Expected $limitcount results from BookPeer::doSelect()");

		// re-create it just to avoid side-effects
		$lc2 = new Criteria();
		$lc2->setLimit($limitcount);
		$results2 = BookPeer::doSelectJoinAuthor($lc2);

		$this->assertEquals($limitcount, count($results2), "Expected $limitcount results from BookPeer::doSelectJoinAuthor()");

	}

	/**
	 * Test the basic functionality of the doSelectJoin*() methods.
	 */
	public function testDoSelectJoin()
	{

		$c = new Criteria();

		$books = BookPeer::doSelect($c);
		$obj = $books[0];
		$size = strlen(serialize($obj));


		$joinBooks = BookPeer::doSelectJoinAuthor($c);
		$obj = $joinBooks[0];
		$joinSize = strlen(serialize($obj));

		$this->assertEquals(count($books), count($joinBooks), "Expected to find same number of rows in doSelectJoin*() call as doSelect() call.");

		$this->assertTrue($joinSize > $size, "Expected a serialized join object to be larger than a non-join object.");
	}

	public function testObjectInstances()
	{

		// 1) make sure consecutive calls to retrieveByPK() return the same object.
		$b1 = BookPeer::retrieveByPK(1);
		$b2 = BookPeer::retrieveByPK(1);

		$sampleval = md5(microtime());

		$this->assertTrue($b1 === $b2, "Expected object instances to match for calls with same retrieveByPK() method signature.");

		// 2) make sure that calls to doSelect also return references to the same objects.
		$allbooks = BookPeer::doSelect(new Criteria());
		foreach($allbooks as $testb) {
			if ($testb->getPrimaryKey() == $b1->getPrimaryKey()) {
				$this->assertTrue($testb === $b1, "Expected same object instance from doSelect() as from retrieveByPK()");
			}
		}

		// 3) test fetching related objects
		$book = BookPeer::retrieveByPK(1);

		$bookauthor = $book->getAuthor();

		$author = AuthorPeer::retrieveByPK($bookauthor->getId());

		$this->assertTrue($bookauthor === $author, "Expected same object instance when calling fk object accessor as retrieveByPK()");

		// 4) test a doSelectJoin()
		$morebooks = BookPeer::doSelectJoinAuthor(new Criteria());
		for($i=0,$j=0; $j < count($morebooks); $i++, $j++) {
			$testb1 = $allbooks[$i];
			$testb2 = $allbooks[$j];
			$this->assertTrue($testb1 === $testb2, "Expected the same objects from consecutive doSelect() calls.");
			// we could probably also test this by just verifying that $book & $testb are the same
			if ($testb1->getPrimaryKey() === $book) {
				$this->assertTrue($book->getAuthor() === $testb1->getAuthor(), "Expected same author object in calls to pkey-matching books.");
			}
		}


		// 5) test creating a new object, saving it, and then retrieving that object (should all be same instance)
		$b = new BookstoreEmployee();
		$b->setName("Testing");
		$b->setJobTitle("Testing");
		$b->save();

		$empId = $b->getId();

		$this->assertSame($b, BookstoreEmployeePeer::retrieveByPK($empId), "Expected newly saved object to be same instance as pooled.");

	}

	/**
	 * Test inheritance features.
	 */
	public function testInheritance()
	{
		$manager = new BookstoreManager();
		$manager->setName("Manager 1");
		$manager->setJobTitle("Warehouse Manager");
		$manager->save();
		$managerId = $manager->getId();

		$employee = new BookstoreEmployee();
		$employee->setName("Employee 1");
		$employee->setJobTitle("Janitor");
		$employee->setSupervisorId($managerId);
		$employee->save();
		$empId = $employee->getId();

		$cashier = new BookstoreCashier();
		$cashier->setName("Cashier 1");
		$cashier->setJobTitle("Cashier");
		$cashier->save();
		$cashierId = $cashier->getId();

		// 1) test the pooled instances'
		$c = new Criteria();
		$c->add(BookstoreEmployeePeer::ID, array($managerId, $empId, $cashierId), Criteria::IN);
		$c->addAscendingOrderByColumn(BookstoreEmployeePeer::ID);

		$objects = BookstoreEmployeePeer::doSelect($c);

		$this->assertEquals(3, count($objects), "Expected 3 objects to be returned.");

		list($o1, $o2, $o3) = $objects;

		$this->assertSame($o1, $manager);
		$this->assertSame($o2, $employee);
		$this->assertSame($o3, $cashier);

		// 2) test a forced reload from database
		BookstoreEmployeePeer::clearInstancePool();

		list($o1,$o2,$o3) = BookstoreEmployeePeer::doSelect($c);

		$this->assertTrue($o1 instanceof BookstoreManager, "Expected BookstoreManager object, got " . get_class($o1));
		$this->assertTrue($o2 instanceof BookstoreEmployee, "Expected BookstoreEmployee object, got " . get_class($o2));
		$this->assertTrue($o3 instanceof BookstoreCashier, "Expected BookstoreCashier object, got " . get_class($o3));

	}

	/**
	 * Tests the return type of doCount*() methods.
	 */
	public function testDoCountType()
	{
		$c = new Criteria();
		$this->assertType('integer', BookPeer::doCount($c), "Expected doCount() to return an integer.");
		$this->assertType('integer', BookPeer::doCountJoinAll($c), "Expected doCountJoinAll() to return an integer.");
		$this->assertType('integer', BookPeer::doCountJoinAuthor($c), "Expected doCountJoinAuthor() to return an integer.");
	}
}
