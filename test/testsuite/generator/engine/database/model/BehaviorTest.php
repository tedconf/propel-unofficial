<?php

/*
 *  $Id: BehaviorTest.php 1133 2009-09-16 13:35:12Z francois $
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

require_once 'PHPUnit/Framework/TestCase.php';
include_once 'propel/engine/database/transform/XmlToAppData.php';
include_once 'propel/engine/platform/MysqlPlatform.php';

/**
 * Tests for Behavior class
 *
 * @author     <a href="mailto:mpoeschl@marmot.at>Martin Poeschl</a>
 * @version    $Revision: 1133 $
 * @package    generator.engine.database.model
 */
class BehaviorTest extends PHPUnit_Framework_TestCase {

	private $xmlToAppData;
	private $appData;

	/**
	 * test if the tables get the package name from the properties file
	 *
	 */
	public function testXmlToAppData() {
		$this->xmlToAppData = new XmlToAppData(new MysqlPlatform(), "defaultpackage", null);
		$this->appData = $this->xmlToAppData->parseFile('fixtures/bookstore/behavior-schema.xml');
    $table = $this->appData->getDatabase("bookstore-behavior")->getTable('b_user');
		$behaviors = $table->getBehaviors();
    $this->assertEquals(count($behaviors), 1, 'XmlToAppData ads as many behaviors as there are behaviors tags');
		$behavior = $table->getBehavior('timestampable');
		$this->assertEquals($behavior->getTable()->getName(), 'b_user', 'XmlToAppData sets the behavior table correctly');
		$this->assertEquals($behavior->getParameters(), array('create_column' => 'created_at'), 'XmlToAppData sets the behavior parameters correctly');
	}
}
