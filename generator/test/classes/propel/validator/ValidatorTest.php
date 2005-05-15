<?php
/*
 * $Id$
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
 * Tests the validator classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various
 * validator operations.
 *
 * The database is relaoded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see BookstoreDataPopulator
 * @author Michael Aichler <aichler@mediacluster.de>
 */
class ValidatorTest extends BookstoreTestBase
{

  /**
  * Test minLength validator.
  * This also tests the ${value} substitution.
  */
  public function testDoValidate_MinLength()
  {
    $book = new Book();
    $book->setTitle("12345"); // min length is 10

    $ret = $book->validate();
    $this->assertSingleValidation($ret, "Book title must be more than 10 characters long.");
  }

  /**
  * Test unique validator.
  */
  public function testDoValidate_Unique()
  {
    $book = new Book();
    $book->setTitle("Don Juan");

    $ret = $book->validate();
    $this->assertSingleValidation($ret, "Book title already in database.");
  }

  /**
  * Test recursive validaton.
  */
  public function testDoValidate_Complex()
  {
    $book = new Book();
    $book->setTitle("12345"); // min length is 10

    $author = new Author();
    $author->setFirstName("Hans"); // last name required, valid email format, age > 0

    $review = new Review();
    $review->setReviewDate("08/09/2001"); // reviewed_by column required, invalid status (new, reviewed, archived)

    $book->setAuthor($author);
    $book->addReview($review);

    $ret = $book->validate();
    /* Make sure 3 validation messages were returned */
    $this->assertEquals(6, count($ret), "");

    /* Make sure correct columns failed */
    $expectedCols = array(
		AuthorPeer::LAST_NAME,
		AuthorPeer::EMAIL,
		AuthorPeer::AGE,
		BookPeer::TITLE,
		ReviewPeer::REVIEWED_BY,
		ReviewPeer::STATUS
	);
    $returnedCols = array_keys($ret);

    /* implode for readability */
    $this->assertEquals(implode(',', $expectedCols), implode(',', $returnedCols));
  }


  protected function assertSingleValidation($ret, $expectedMsg)
  {
    /* Make sure validation failed */
    $this->assertTrue($ret !== true, "Expected validation to fail !");

    /* Make sure 1 validation message was returned */
    $count = count($ret);
    $this->assertTrue($count === 1, "Expected that exactly one validation failed ($count) !");

    /* Make sure expected validation message was returned */
    $el = array_shift($ret);
    $this->assertEquals($el->getMessage(), $expectedMsg, "Got unexpected validation failed message: " . $el->getMessage());
  }

}