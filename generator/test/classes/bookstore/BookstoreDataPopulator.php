<?php
use bookstore\Model as PropelModel;
use bookstore\Peer as PropelPeer;

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
 * and is licensed under version 3 of the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

define('_LOB_SAMPLE_FILE_PATH', dirname(__FILE__) . '/../../etc/lob');

/**
 * Populates data needed by the bookstore unit tests.
 *
 * This classes uses the actual Propel objects to do the population rather than
 * inserting directly into the database.  This will have a performance hit, but will
 * benefit from increased flexibility (as does anything using Propel).
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 */
class BookstoreDataPopulator {

	public static function populate()
	{
		// Add publisher records
		// ---------------------

		$scholastic = new PropelModel\Publisher();
		$scholastic->setName("Scholastic");
		// do not save, will do later to test cascade

		$morrow = new PropelModel\Publisher();
		$morrow->setName("William Morrow");
		$morrow->save();
		$morrow_id = $morrow->getId();

		$penguin = new PropelModel\Publisher();
		$penguin->setName("Penguin");
		$penguin->save();
		$penguin_id = $penguin->getId();

		$vintage = new PropelModel\Publisher();
		$vintage->setName("Vintage");
		$vintage->save();
		$vintage_id = $vintage->getId();

		$rowling = new PropelModel\Author();
		$rowling->setFirstName("J.K.");
		$rowling->setLastName("Rowling");
		// no save()

		$stephenson = new PropelModel\Author();
		$stephenson->setFirstName("Neal");
		$stephenson->setLastName("Stephenson");
		$stephenson->save();
		$stephenson_id = $stephenson->getId();

		$byron = new PropelModel\Author();
		$byron->setFirstName("George");
		$byron->setLastName("Byron");
		$byron->save();
		$byron_id = $byron->getId();

		$grass = new PropelModel\Author();
		$grass->setFirstName("Gunter");
		$grass->setLastName("Grass");
		$grass->save();
		$grass_id = $grass->getId();

		$phoenix = new PropelModel\Book();
		$phoenix->setTitle("Harry Potter and the Order of the Phoenix");
		$phoenix->setISBN("043935806X");
		$phoenix->setAuthor($rowling);
		$phoenix->setPublisher($scholastic);
		$phoenix->setPrice(10.99);
		$phoenix->save();
		$phoenix_id = $phoenix->getId();

		$qs = new PropelModel\Book();
		$qs->setISBN("0380977427");
		$qs->setTitle("Quicksilver");
		$qs->setPrice(11.99);
		$qs->setAuthor($stephenson);
		$qs->setPublisher($morrow);
		$qs->save();
		$qs_id = $qs->getId();

		$dj = new PropelModel\Book();
		$dj->setISBN("0140422161");
		$dj->setTitle("Don Juan");
		$dj->setPrice(12.99);
		$dj->setAuthor($byron);
		$dj->setPublisher($penguin);
		$dj->save();
		$dj_id = $dj->getId();

		$td = new PropelModel\Book();
		$td->setISBN("067972575X");
		$td->setTitle("The Tin Drum");
		$td->setPrice(13.99);
		$td->setAuthor($grass);
		$td->setPublisher($vintage);
		$td->save();
		$td_id = $td->getId();

		$r1 = new PropelModel\Review();
		$r1->setBook($phoenix);
		$r1->setReviewedBy("Washington Post");
		$r1->setRecommended(true);
		$r1->setReviewDate(time());
		$r1->save();
		$r1_id = $r1->getId();

		$r2 = new PropelModel\Review();
		$r2->setBook($phoenix);
		$r2->setReviewedBy("New York Times");
		$r2->setRecommended(false);
		$r2->setReviewDate(time());
		$r2->save();
		$r2_id = $r2->getId();

		$blob_path = _LOB_SAMPLE_FILE_PATH . '/tin_drum.gif';
		$clob_path =  _LOB_SAMPLE_FILE_PATH . '/tin_drum.txt';

		$m1 = new PropelModel\Media();
		$m1->setBook($td);
		$m1->setCoverImage(file_get_contents($blob_path));
		$m1->setExcerpt(file_get_contents($clob_path));
		$m1->save();

		// Add book list records
		// ---------------------
		// (this is for many-to-many tests)

		$blc1 = new PropelModel\BookClubList();
		$blc1->setGroupLeader("Crazyleggs");
		$blc1->setTheme("Happiness");

		$brel1 = new PropelModel\BookListRel();
		$brel1->setBook($phoenix);

		$brel2 = new PropelModel\BookListRel();
		$brel2->setBook($dj);

		$blc1->addBookListRel($brel1);
		$blc1->addBookListRel($brel2);

		$bemp1 = new PropelModel\BookstoreEmployee();
		$bemp1->setName("John");
		$bemp1->setJobTitle("Manager");

		$bemp2 = new PropelModel\BookstoreEmployee();
		$bemp2->setName("Pieter");
		$bemp2->setJobTitle("Clerk");
		$bemp2->setSupervisor($bemp1);
		$bemp2->save();

		$role = new PropelModel\AcctAccessRole();
		$role->setName("Admin");

		$bempacct = new PropelModel\BookstoreEmployeeAccount();
		$bempacct->setBookstoreEmployee($bemp1);
		$bempacct->setAcctAccessRole($role);
		$bempacct->setLogin("john");
		$bempacct->setPassword("johnp4ss");
		$bempacct->save();

		// Add bookstores

		$store = new PropelModel\Bookstore();
		$store->setStoreName("Amazon");
		$store->setPopulationServed(5000000000); // world population
		$store->setTotalBooks(300);
		$store->save();

		$store = new PropelModel\Bookstore();
		$store->setStoreName("Local Store");
		$store->setPopulationServed(20);
		$store->setTotalBooks(500000);
		$store->save();
	}

	public static function depopulate()
	{
		//@todo figure out why I had to qualify these but not the stuff above.
		PropelPeer\AcctAccessRolePeer::doDeleteAll();
		PropelPeer\AuthorPeer::doDeleteAll();
		PropelPeer\BookstorePeer::doDeleteAll();
		PropelPeer\BookstoreContestPeer::doDeleteAll();
		PropelPeer\BookstoreContestEntryPeer::doDeleteAll();
		PropelPeer\BookstoreEmployeePeer::doDeleteAll();
		PropelPeer\BookstoreEmployeeAccountPeer::doDeleteAll();
		PropelPeer\BookstoreSalePeer::doDeleteAll();
		PropelPeer\BookClubListPeer::doDeleteAll();
		PropelPeer\BookOpinionPeer::doDeleteAll();
		PropelPeer\BookReaderPeer::doDeleteAll();
		PropelPeer\BookListRelPeer::doDeleteAll();
		PropelPeer\BookPeer::doDeleteAll();
		PropelPeer\ContestPeer::doDeleteAll();
		PropelPeer\CustomerPeer::doDeleteAll();
		PropelPeer\MediaPeer::doDeleteAll();
		PropelPeer\PublisherPeer::doDeleteAll();
		PropelPeer\ReaderFavoritePeer::doDeleteAll();
		PropelPeer\ReviewPeer::doDeleteAll();
	}

}
