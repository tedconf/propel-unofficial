<?php

use bookstore\Peer as PropelPeer;
use bookstore\Model as PropelModel;

/*
 *  $Id: GeneratedNestedSetPeerTest.php 989 2008-03-11 14:29:30Z heltem $
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

require_once 'cms/CmsTestBase.php';

/**
 * Tests the generated nested-set Object classes.
 *
 * This test uses generated Bookstore-Cms classes to test the behavior of various
 * object operations.  The _idea_ here is to test every possible generated method
 * from Object.tpl; if necessary, bookstore will be expanded to accommodate this.
 *
 * The database is relaoded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the CmsDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see        CmsDataPopulator
 */
class GeneratedNestedSetPeerTest extends CmsTestBase {

	/**
	 * Test retrieveRoot() as true
	 */
	public function testRetrieveRootExist()
	{
		$pp = PropelPeer\PagePeer::retrieveRoot(1);
		$this->assertNotNull($pp, 'Node must exist and not be null');
		$this->assertEquals(1, $pp->getLeftValue(), 'Node left value must be equal to 1');
	}

	/**
	 * Test retrieveRoot() as false
	 */
	public function testRetrieveRootNotExist()
	{
		$pp = PropelPeer\PagePeer::retrieveRoot(2);
		$this->assertNull($pp, 'Root with such scopeId must not exist');
	}

	/**
	 * Test xxxNestedSetPeer::isRoot() as true
	 */
	public function testPeerIsRootTrue()
	{
		$pp = PropelPeer\PagePeer::retrieveRoot(1);
		$this->assertTrue(PropelPeer\PagePeer::isRoot($pp), 'Node must be root');
	}

	/**
	 * Test xxxNestedSetPeer::isRoot() as false
	 */
	public function testPeerIsRootFalse()
	{
		$c = new Criteria(PropelPeer\PagePeer::DATABASE_NAME);
		$c->add(PropelPeer\PagePeer::TITLE, 'school', Criteria::EQUAL);

		$school = PropelPeer\PagePeer::doSelectOne($c);
		$this->assertFalse(PropelPeer\PagePeer::isRoot($school), 'Node must not be root');
	}

	/**
	 * Test xxxNestedSetPeer::retrieveParent() as true.
	 */
	public function testPeerRetrieveParentTrue()
	{
		$c = new Criteria(PropelPeer\PagePeer::DATABASE_NAME);
		$c->add(PropelPeer\PagePeer::TITLE, 'school', Criteria::EQUAL);

		$school = PropelPeer\PagePeer::doSelectOne($c);
		$this->assertNotNull(PropelPeer\PagePeer::retrieveParent($school), 'Parent node must exist');
	}

	/**
	 * Test xxxNestedSetPeer::retrieveParent() as false.
	 */
	public function testPeerRetrieveParentFalse()
	{
		$c = new Criteria(PropelPeer\PagePeer::DATABASE_NAME);
		$c->add(PropelPeer\PagePeer::TITLE, 'home', Criteria::EQUAL);

		$home = PropelPeer\PagePeer::doSelectOne($c);
		$this->assertNull(PropelPeer\PagePeer::retrieveParent($home), 'Parent node must not exist and retrieved not be null');
	}

	/**
	 * Test xxxNestedSetPeer::hasParent() as true.
	 */
	public function testPeerHasParentTrue()
	{
		$c = new Criteria();
		$c->add(PropelPeer\PagePeer::TITLE, 'school', Criteria::EQUAL);

		$school = PropelPeer\PagePeer::doSelectOne($c);
		$this->assertTrue(PropelPeer\PagePeer::hasParent($school), 'Node must have parent node');
	}

	/**
	 * Test xxxNestedSetPeer::hasParent() as false
	 */
	public function testPeerHasParentFalse()
	{
		$c = new Criteria();
		$c->add(PropelPeer\PagePeer::TITLE, 'home', Criteria::EQUAL);

		$home = PropelPeer\PagePeer::doSelectOne($c);
		$this->assertFalse(PropelPeer\PagePeer::hasParent($home), 'Root node must not have parent');
	}

	/**
	 * Test xxxNestedSetPeer::isValid() as true.
	 */
	public function testPeerIsValidTrue()
	{
		$c = new Criteria();
		$c->add(PropelPeer\PagePeer::TITLE, 'school', Criteria::EQUAL);

		$school = PropelPeer\PagePeer::doSelectOne($c);
		$this->assertTrue(PropelPeer\PagePeer::isValid($school), 'Node must be valid');
	}

	/**
	 * Test xxxNestedSetPeer::isValid() as false
	 */
	public function testPeerIsValidFalse()
	{
		$page = new PropelModel\Page();
		$this->assertFalse(PropelPeer\PagePeer::isValid($page), 'Node left and right values must be invalid');
		$this->assertFalse(PropelPeer\PagePeer::isValid(null), 'Null must be invalid');
	}

	/**
	 * Test xxxNestedSetPeer::isLeaf() as true.
	 */
	public function testPeerIsLeafTrue()
	{
		$c = new Criteria();
		$c->add(PropelPeer\PagePeer::TITLE, 'simulator', Criteria::EQUAL);

		$simulator = PropelPeer\PagePeer::doSelectOne($c);
		$this->assertTrue(PropelPeer\PagePeer::isLeaf($simulator), 'Node must be a leaf');
	}

	/**
	 * Test xxxNestedSetPeer::isLeaf() as false
	 */
	public function testPeerIsLeafFalse()
	{
		$c = new Criteria();
		$c->add(PropelPeer\PagePeer::TITLE, 'contact', Criteria::EQUAL);

		$contact = PropelPeer\PagePeer::doSelectOne($c);
		$this->assertFalse(PropelPeer\PagePeer::isLeaf($contact), 'Node must not be a leaf');
	}

	/**
	 * Test xxxNestedSetPeer::createRoot()
	 */
	public function testPeerCreateRoot()
	{
		$page = new PropelModel\Page();
		PropelPeer\PagePeer::createRoot($page);
		$this->assertEquals(1, $page->getLeftValue(), 'Node left value must equal 1');
		$this->assertEquals(2, $page->getRightValue(), 'Node right value must equal 2');
	}

	/**
	 * Test xxxNestedSetPeer::createRoot() exception
	 * @expectedException PropelException
	 */
	public function testPeerCreateRootException()
	{
		$c = new Criteria();
		$c->add(PropelPeer\PagePeer::TITLE, 'home', Criteria::EQUAL);

		$home = PropelPeer\PagePeer::doSelectOne($c);
		PropelPeer\PagePeer::createRoot($home);
	}

}

?>
