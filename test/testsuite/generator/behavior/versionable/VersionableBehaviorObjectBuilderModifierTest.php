<?php

/*
 *	$Id: VersionableBehaviorTest.php 1460 2010-01-17 22:36:48Z francois $
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../../../generator/lib/util/PropelQuickBuilder.php';
require_once dirname(__FILE__) . '/../../../../../generator/lib/behavior/versionable/VersionableBehavior.php';
require_once dirname(__FILE__) . '/../../../../../runtime/lib/Propel.php';

/**
 * Tests for VersionableBehavior class
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    generator.behavior.versionable
 */
class VersionableBehaviorObjectBuilderModifierTest extends PHPUnit_Framework_TestCase
{

	public function setUp()
	{
		if (!class_exists('VersionableBehaviorTest1')) {
			$schema = <<<EOF
<database name="versionable_behavior_test_1">
	<table name="versionable_behavior_test_1">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="bar" type="INTEGER" />
		<behavior name="versionable" />
	</table>
	<table name="versionable_behavior_test_2">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="bar" type="INTEGER" />
		<behavior name="versionable">
			<parameter name="version_column" value="foo_ver" />
		</behavior>
	</table>
	<table name="versionable_behavior_test_3">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="bar" type="INTEGER" />
		<behavior name="versionable">
			<parameter name="version_table" value="foo_ver" />
		</behavior>
	</table>
	<table name="versionable_behavior_test_4">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="bar" type="INTEGER" />
		<behavior name="versionable">
			<parameter name="log_created_at" value="true" />
			<parameter name="log_created_by" value="true" />
			<parameter name="log_comment" value="true" />
		</behavior>
	</table>
	<table name="versionable_behavior_test_5">
		<column name="id" primaryKey="true" type="INTEGER" autoIncrement="true" />
		<column name="foo" type="VARCHAR" size="100" />
		<column name="foreign_id" type="INTEGER" />
		<foreign-key foreignTable="versionable_behavior_test_4">
			<reference local="foreign_id" foreign="id" />
		</foreign-key>
		<behavior name="versionable" />
	</table>

</database>
EOF;
			PropelQuickBuilder::buildSchema($schema);
		}
	}

	public function testGetVersionExists()
	{
		$this->assertTrue(method_exists('VersionableBehaviorTest1', 'getVersion'));
		$this->assertTrue(method_exists('VersionableBehaviorTest2', 'getVersion'));
	}

	public function testSetVersionExists()
	{
		$this->assertTrue(method_exists('VersionableBehaviorTest1', 'setVersion'));
		$this->assertTrue(method_exists('VersionableBehaviorTest2', 'setVersion'));
	}
	
	public function providerForNewActiveRecordTests()
	{
		return array(
			array('VersionableBehaviorTest1'),
			array('VersionableBehaviorTest2'),
		);
	}

	/**
	 * @dataProvider providerForNewActiveRecordTests
	 */
	public function testVersionGetterAndSetter($class)
	{
		$o = new $class;
		$o->setVersion(1234);
		$this->assertEquals(1234, $o->getVersion());
	}
	
	/**
	 * @dataProvider providerForNewActiveRecordTests
	 */
	public function testVersionDefaultValue($class)
	{
		$o = new $class;
		$this->assertEquals(0, $o->getVersion());
	}

	/**
	 * @dataProvider providerForNewActiveRecordTests
	 */
	public function testVersionValueInitializesOnInsert($class)
	{
		$o = new $class;
		$o->save();
		$this->assertEquals(1, $o->getVersion());
	}

	/**
	 * @dataProvider providerForNewActiveRecordTests
	 */
	public function testVersionValueIncrementsOnUpdate($class)
	{
		$o = new $class;
		$o->save();
		$this->assertEquals(1, $o->getVersion());
		$o->setBar(12);
		$o->save();
		$this->assertEquals(2, $o->getVersion());
		$o->setBar(13);
		$o->save();
		$this->assertEquals(3, $o->getVersion());
		$o->setBar(12);
		$o->save();
		$this->assertEquals(4, $o->getVersion());
	}

	/**
	 * @dataProvider providerForNewActiveRecordTests
	 */
	public function testVersionDoesNotIncrementOnUpdateWithNoChange($class)
	{
		$o = new $class;
		$o->setBar(12);
		$o->save();
		$this->assertEquals(1, $o->getVersion());
		$o->setBar(12);
		$o->save();
		$this->assertEquals(1, $o->getVersion());
	}

	/**
	 * @dataProvider providerForNewActiveRecordTests
	 */
	public function testVersionDoesNotIncrementWhenVersioningIsDisabled($class)
	{
		$o = new $class;
		VersionableBehaviorTest1Peer::disableVersioning();
		VersionableBehaviorTest2Peer::disableVersioning();
		$o->setBar(12);
		$o->save();
		$this->assertEquals(0, $o->getVersion());
		$o->setBar(13);
		$o->save();
		$this->assertEquals(0, $o->getVersion());
		VersionableBehaviorTest1Peer::enableVersioning();
		VersionableBehaviorTest2Peer::enableVersioning();

	}
	
	public function testNewVersionCreatesRecordInVersionTable()
	{
		VersionableBehaviorTest1Query::create()->deleteAll();
		VersionableBehaviorTest1VersionQuery::create()->deleteAll();
		$o = new VersionableBehaviorTest1();
		$o->save();
		$versions = VersionableBehaviorTest1VersionQuery::create()->find();
		$this->assertEquals(1, $versions->count());
		$this->assertEquals($o, $versions[0]->getVersionableBehaviorTest1());
		$o->save();
		$versions = VersionableBehaviorTest1VersionQuery::create()->find();
		$this->assertEquals(1, $versions->count());
		$o->setBar(123);
		$o->save();
		$versions = VersionableBehaviorTest1VersionQuery::create()->orderByVersion()->find();
		$this->assertEquals(2, $versions->count());
		$this->assertEquals($o->getId(), $versions[0]->getId());
		$this->assertNull($versions[0]->getBar());
		$this->assertEquals($o->getId(), $versions[1]->getId());
		$this->assertEquals(123, $versions[1]->getBar());
	}
	
	public function testNewVersionCreatesRecordInVersionTableWithCustomName()
	{
		VersionableBehaviorTest3Query::create()->deleteAll();
		VersionableBehaviorTest3VersionQuery::create()->deleteAll();
		$o = new VersionableBehaviorTest3();
		$o->save();
		$versions = VersionableBehaviorTest3VersionQuery::create()->find();
		$this->assertEquals(1, $versions->count());
		$this->assertEquals($o, $versions[0]->getVersionableBehaviorTest3());
		$o->save();
		$versions = VersionableBehaviorTest3VersionQuery::create()->find();
		$this->assertEquals(1, $versions->count());
		$o->setBar(123);
		$o->save();
		$versions = VersionableBehaviorTest3VersionQuery::create()->orderByVersion()->find();
		$this->assertEquals(2, $versions->count());
		$this->assertEquals($o->getId(), $versions[0]->getId());
		$this->assertNull($versions[0]->getBar());
		$this->assertEquals($o->getId(), $versions[1]->getId());
		$this->assertEquals(123, $versions[1]->getBar());
	}

	public function testNewVersionDoesNotCreateRecordInVersionTableWhenVersioningIsDisabled()
	{
		VersionableBehaviorTest1Query::create()->deleteAll();
		VersionableBehaviorTest1VersionQuery::create()->deleteAll();
		VersionableBehaviorTest1Peer::disableVersioning();
		$o = new VersionableBehaviorTest1();
		$o->save();
		$versions = VersionableBehaviorTest1VersionQuery::create()->find();
		$this->assertEquals(0, $versions->count());
		VersionableBehaviorTest1Peer::enableVersioning();
	}

	public function testDeleteObjectDeletesRecordInVersionTable()
	{
		VersionableBehaviorTest1Query::create()->deleteAll();
		VersionableBehaviorTest1VersionQuery::create()->deleteAll();
		$o = new VersionableBehaviorTest1();
		$o->save();
		$o->setBar(123);
		$o->save();
		$nbVersions = VersionableBehaviorTest1VersionQuery::create()->count();
		$this->assertEquals(2, $nbVersions);
		$o->delete();
		$nbVersions = VersionableBehaviorTest1VersionQuery::create()->count();
		$this->assertEquals(0, $nbVersions);
	}

	public function testDeleteObjectDeletesRecordInVersionTableWithCustomName()
	{
		VersionableBehaviorTest3Query::create()->deleteAll();
		VersionableBehaviorTest3VersionQuery::create()->deleteAll();
		$o = new VersionableBehaviorTest3();
		$o->save();
		$o->setBar(123);
		$o->save();
		$nbVersions = VersionableBehaviorTest3VersionQuery::create()->count();
		$this->assertEquals(2, $nbVersions);
		$o->delete();
		$nbVersions = VersionableBehaviorTest3VersionQuery::create()->count();
		$this->assertEquals(0, $nbVersions);
	}
	
	public function testToVersion()
	{
		$o = new VersionableBehaviorTest1();
		$o->setBar(123); // version 1
		$o->save();
		$o->setBar(456); // version 2
		$o->save();
		$o->toVersion(1);
		$this->assertEquals(123, $o->getBar());
		$o->toVersion(2);
		$this->assertEquals(456, $o->getBar());
	}
	
	public function testToVersionAllowsFurtherSave()
	{
		$o = new VersionableBehaviorTest1();
		$o->setBar(123); // version 1
		$o->save();
		$o->setBar(456); // version 2
		$o->save();
		$o->toVersion(1);
		$this->assertTrue($o->isModified());
		$o->save();
		$this->assertEquals(3, $o->getVersion());
	}

	/**
	 * @expectedException PropelException
	 */
	public function testToVersionThrowsExceptionOnIncorrectVersion()
	{
		$o = new VersionableBehaviorTest1();
		$o->setBar(123); // version 1
		$o->save();
		$o->toVersion(2);
	}

	public function testToVersionPreservesVersionedFkObjects()
	{
		$a = new VersionableBehaviorTest4();
		$a->setBar(123); // a1
		$b = new VersionableBehaviorTest5();
		$b->setFoo('Hello');
		$b->setVersionableBehaviorTest4($a);
		$b->save(); //b1
		$a->setBar(456); //a2
		$b->save(); // b2
		$b->setFoo('World');
		$b->save(); // b3
		$b->toVersion(2);
		$this->assertEquals($b->getVersion(), 2);
		$this->assertEquals($b->getVersionableBehaviorTest4()->getVersion(), 2);
		$b->toVersion(1);
		$this->assertEquals($b->getVersion(), 1);
		$this->assertEquals($b->getVersionableBehaviorTest4()->getVersion(), 1);
		$b->toVersion(3);
		$this->assertEquals($b->getVersion(), 3);
		$this->assertEquals($b->getVersionableBehaviorTest4()->getVersion(), 2);
	}

	public function testToVersionPreservesVersionedReferrerObjects()
	{
		$b1 = new VersionableBehaviorTest5();
		$b1->setFoo('Hello');
		$b2 = new VersionableBehaviorTest5();
		$b2->setFoo('World');
		$a = new VersionableBehaviorTest4();
		$a->setBar(123); // a1
		$a->addVersionableBehaviorTest5($b1);
		$a->addVersionableBehaviorTest5($b2);
		$a->save(); //b1
		$this->assertEquals(1, $a->getVersion());
		$bs = $a->getVersionableBehaviorTest5s();
		$this->assertEquals(1, $bs[0]->getVersion());
		$this->assertEquals(1, $bs[1]->getVersion());
		$b1->setFoo('Heloo');
		$a->save();
		$this->assertEquals(2, $a->getVersion());
		$bs = $a->getVersionableBehaviorTest5s();
		$this->assertEquals(2, $bs[0]->getVersion());
		$this->assertEquals(1, $bs[1]->getVersion());
		$b3 = new VersionableBehaviorTest5();
		$b3->setFoo('Yep');
		$a->clearVersionableBehaviorTest5s();
		$a->addVersionableBehaviorTest5($b3);
		$a->save();
		$a->clearVersionableBehaviorTest5s();
		$this->assertEquals(3, $a->getVersion());
		$bs = $a->getVersionableBehaviorTest5s();
		$this->assertEquals(2, $bs[0]->getVersion());
		$this->assertEquals(1, $bs[1]->getVersion());
		$this->assertEquals(1, $bs[2]->getVersion());
	}

	public function testGetLastVersionNumber()
	{
		$o = new VersionableBehaviorTest1();
		$this->assertEquals(0, $o->getLastVersionNumber());
		$o->setBar(123); // version 1
		$o->save();
		$this->assertEquals(1, $o->getLastVersionNumber());
		$o->setBar(456); // version 2
		$o->save();
		$this->assertEquals(2, $o->getLastVersionNumber());
		$o->toVersion(1);
		$o->save();
		$this->assertEquals(3, $o->getLastVersionNumber());
	}
	
	public function testIsLastVersion()
	{
		$o = new VersionableBehaviorTest1();
		$this->assertTrue($o->isLastVersion());
		$o->setBar(123); // version 1
		$o->save();
		$this->assertTrue($o->isLastVersion());
		$o->setBar(456); // version 2
		$o->save();
		$this->assertTrue($o->isLastVersion());
		$o->toVersion(1);
		$this->assertFalse($o->isLastVersion());
		$o->save();
		$this->assertTrue($o->isLastVersion());
	}
	
	public function testIsVersioningNecessary()
	{
		$o = new VersionableBehaviorTest1();
		$this->assertTrue($o->isVersioningNecessary());
		$o->save();
		$this->assertFalse($o->isVersioningNecessary());
		$o->setBar(123);
		$this->assertTrue($o->isVersioningNecessary());
		$o->save();
		$this->assertFalse($o->isVersioningNecessary());

		VersionableBehaviorTest1Peer::disableVersioning();
		$o = new VersionableBehaviorTest1();
		$this->assertFalse($o->isVersioningNecessary());
		$o->save();
		$this->assertFalse($o->isVersioningNecessary());
		$o->setBar(123);
		$this->assertFalse($o->isVersioningNecessary());
		$o->save();
		$this->assertFalse($o->isVersioningNecessary());
		VersionableBehaviorTest1Peer::enableVersioning();
	}
	
	public function testAddVersionNewObject()
	{
		VersionableBehaviorTest1Peer::disableVersioning();
		VersionableBehaviorTest1Query::create()->deleteAll();
		VersionableBehaviorTest1VersionQuery::create()->deleteAll();
		$o = new VersionableBehaviorTest1();
		$o->addVersion();
		$o->save();
		$versions = VersionableBehaviorTest1VersionQuery::create()->find();
		$this->assertEquals(1, $versions->count());
		$this->assertEquals($o, $versions[0]->getVersionableBehaviorTest1());
		VersionableBehaviorTest1Peer::enableVersioning();
	}

	public function testVersionCreatedAt()
	{
		$o = new VersionableBehaviorTest4();
		$t = time();
		$o->save();
		$version = VersionableBehaviorTest4VersionQuery::create()
			->filterByVersionableBehaviorTest4($o)
			->findOne();
		$this->assertEquals($t, $version->getVersionCreatedAt('U'));
		
		$o = new VersionableBehaviorTest4();
		$inThePast = time() - 123456;
		$o->setVersionCreatedAt($inThePast);
		$o->save();
		$this->assertEquals($inThePast, $o->getVersionCreatedAt('U'));
		$version = VersionableBehaviorTest4VersionQuery::create()
			->filterByVersionableBehaviorTest4($o)
			->findOne();
		$this->assertEquals($o->getVersionCreatedAt(), $version->getVersionCreatedAt());
	}

	public function testVersionCreatedBy()
	{
		$o = new VersionableBehaviorTest4();
		$o->setVersionCreatedBy('me me me');
		$o->save();
		$version = VersionableBehaviorTest4VersionQuery::create()
			->filterByVersionableBehaviorTest4($o)
			->findOne();
		$this->assertEquals('me me me', $version->getVersionCreatedBy());
	}

	public function testVersionComment()
	{
		$o = new VersionableBehaviorTest4();
		$o->setVersionComment('Because you deserve it');
		$o->save();
		$version = VersionableBehaviorTest4VersionQuery::create()
			->filterByVersionableBehaviorTest4($o)
			->findOne();
		$this->assertEquals('Because you deserve it', $version->getVersionComment());
	}

	public function testToVersionWorksWithComments()
	{
		$o = new VersionableBehaviorTest4();
		$o->setVersionComment('Because you deserve it');
		$o->setBar(123); // version 1
		$o->save();
		$o->setVersionComment('Unless I change my mind');
		$o->setBar(456); // version 2
		$o->save();
		$o->toVersion(1);
		$this->assertEquals('Because you deserve it', $o->getVersionComment());
		$o->toVersion(2);
		$this->assertEquals('Unless I change my mind', $o->getVersionComment());
	}

	public function testGetOneVersion()
	{
		$o = new VersionableBehaviorTest1();
		$o->setBar(123); // version 1
		$o->save();
		$o->setBar(456); // version 2
		$o->save();
		$version = $o->getOneVersion(1);
		$this->assertTrue($version instanceof VersionableBehaviorTest1Version);
		$this->assertEquals(1, $version->getVersion());
		$this->assertEquals(123, $version->getBar());
		$version = $o->getOneVersion(2);
		$this->assertEquals(2, $version->getVersion());
		$this->assertEquals(456, $version->getBar());
	}
		
	public function testGetAllVersions()
	{
		$o = new VersionableBehaviorTest1();
		$versions = $o->getAllVersions();
		$this->assertTrue($versions->isEmpty());
		$o->setBar(123); // version 1
		$o->save();
		$o->setBar(456); // version 2
		$o->save();
		$versions = $o->getAllVersions();
		$this->assertTrue($versions instanceof PropelObjectCollection);
		$this->assertEquals(2, $versions->count());
		$this->assertEquals(1, $versions[0]->getVersion());
		$this->assertEquals(123, $versions[0]->getBar());
		$this->assertEquals(2, $versions[1]->getVersion());
		$this->assertEquals(456, $versions[1]->getBar());
	}
	
	public function testCompareVersions()
	{
		$o = new VersionableBehaviorTest4();
		$versions = $o->getAllVersions();
		$this->assertTrue($versions->isEmpty());
		$o->setBar(123); // version 1
		$o->save();
		$o->setBar(456); // version 2
		$o->save();
		$o->setBar(789); // version 3
		$o->setVersionComment('Foo');
		$o->save();
		$diff = $o->compareVersions(1, 3);
		$expected = array(
			'Bar' => array(1 => 123, 3 => 789)
		);
		$this->assertEquals($expected, $diff);
		$diff = $o->compareVersions(1, 3, 'versions');
		$expected = array(
			1 => array('Bar' => 123),
			3 => array('Bar' => 789)
		);
		$this->assertEquals($expected, $diff);
	}
	
	public function testForeignKeyVersion()
	{
		$a = new VersionableBehaviorTest4();
		$a->setBar(123); // a1
		$b = new VersionableBehaviorTest5();
		$b->setFoo('Hello');
		$b->setVersionableBehaviorTest4($a);
		$b->save(); //b1
		$this->assertEquals($b->getVersion(), 1);
		$this->assertEquals($b->getVersionableBehaviorTest4()->getVersion(), 1);
		$a->setBar(456); //a2
		$b->save(); // b2
		$this->assertEquals($b->getVersion(), 2);
		$this->assertEquals($b->getVersionableBehaviorTest4()->getVersion(), 2);
		$b->setFoo('World');
		$b->save(); // b3
		$this->assertEquals($b->getVersion(), 3);
		$this->assertEquals($b->getVersionableBehaviorTest4()->getVersion(), 2);
	}
	
	public function testReferrerVersion()
	{
		$b1 = new VersionableBehaviorTest5();
		$b1->setFoo('Hello');
		$b2 = new VersionableBehaviorTest5();
		$b2->setFoo('World');
		$a = new VersionableBehaviorTest4();
		$a->setBar(123); // a1
		$a->addVersionableBehaviorTest5($b1);
		$a->addVersionableBehaviorTest5($b2);
		$a->save(); //b1
		$this->assertEquals(1, $a->getVersion());
		$this->assertEquals(array(1, 1), $a->getOneVersion(1)->getVersionableBehaviorTest5Versions());
		$b1->setFoo('Heloo');
		$a->save();
		$this->assertEquals(2, $a->getVersion());
		$this->assertEquals(array(2, 1), $a->getOneVersion(2)->getVersionableBehaviorTest5Versions());
		$b3 = new VersionableBehaviorTest5();
		$b3->setFoo('Yep');
		$a->clearVersionableBehaviorTest5s();
		$a->addVersionableBehaviorTest5($b3);
		$a->save();
		$a->clearVersionableBehaviorTest5s();
		$this->assertEquals(3, $a->getVersion());
		$this->assertEquals(array(2, 1, 1), $a->getOneVersion(3)->getVersionableBehaviorTest5Versions());
	}
}