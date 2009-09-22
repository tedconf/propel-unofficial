<?php

require_once 'PHPUnit/Framework/TestCase.php';
include_once 'propel/map/RelationMap.php';
include_once 'propel/map/TableMap.php';

/**
 * Test class for RelationMap.
 *
 * @author     François Zaninotto
 * @version    $Id: RelationMapTest.php 1121 2009-09-14 17:20:11Z francois $
 * @package    runtime.map
 */
class RelationMapTest extends PHPUnit_Framework_TestCase 
{ 
  protected $databaseMap, $relationName, $rmap;

  protected function setUp()
  {
    parent::setUp();
    $this->databaseMap = new DatabaseMap('foodb');
    $this->relationName = 'foo';
    $this->rmap = new RelationMap($this->relationName);
  }

  public function testConstructor()
  {
    $this->assertEquals($this->relationName, $this->rmap->getName(), 'constructor sets the relation name');
  }
  
  public function testLocalTable()
  {
    $this->assertNull($this->rmap->getLocalTable(), 'A new relation has no local table');
    $tmap1 = new TableMap('foo', $this->databaseMap);
    $this->rmap->setLocalTable($tmap1);
    $this->assertEquals($tmap1, $this->rmap->getLocalTable(), 'The local table is set by setLocalTable()');
  }

  public function testForeignTable()
  {
    $this->assertNull($this->rmap->getForeignTable(), 'A new relation has no foreign table');
    $tmap2 = new TableMap('bar', $this->databaseMap);
    $this->rmap->setForeignTable($tmap2);
    $this->assertEquals($tmap2, $this->rmap->getForeignTable(), 'The foreign table is set by setForeignTable()');
  }
  
  public function testType()
  {
    $this->assertNull($this->rmap->getType(), 'A new relation has no type');
    $this->rmap->setType(RelationMap::ONE_TO_MANY);
    $this->assertEquals(RelationMap::ONE_TO_MANY, $this->rmap->getType(), 'The type is set by setType()');
  }
  
  public function testColumns()
  {
    $this->assertEquals(array(), $this->rmap->getLocalColumns(), 'A new relation has no local columns');
    $this->assertEquals(array(), $this->rmap->getForeignColumns(), 'A new relation has no foreign columns');
    $tmap1 = new TableMap('foo', $this->databaseMap);
    $col1 = $tmap1->addColumn('FOO1', 'Foo1PhpName', 'INTEGER');
    $tmap2 = new TableMap('bar', $this->databaseMap);
    $col2 = $tmap2->addColumn('BAR1', 'Bar1PhpName', 'INTEGER');
    $this->rmap->addColumnMapping($col1, $col2);
    $this->assertEquals(array($col1), $this->rmap->getLocalColumns(), 'addColumnMapping() adds a local table');
    $this->assertEquals(array($col2), $this->rmap->getForeignColumns(), 'addColumnMapping() adds a foreign table');
    $expected = array('foo.FOO1' => 'bar.BAR1');
    $this->assertEquals($expected, $this->rmap->getColumnMappings(), 'getColumnMappings() returns an associative array of column mappings');
    $col3 = $tmap1->addColumn('FOOFOO', 'FooFooPhpName', 'INTEGER');
    $col4 = $tmap2->addColumn('BARBAR', 'BarBarPhpName', 'INTEGER');
    $this->rmap->addColumnMapping($col3, $col4);
    $this->assertEquals(array($col1, $col3), $this->rmap->getLocalColumns(), 'addColumnMapping() adds a local table');
    $this->assertEquals(array($col2, $col4), $this->rmap->getForeignColumns(), 'addColumnMapping() adds a foreign table');
    $expected = array('foo.FOO1' => 'bar.BAR1', 'foo.FOOFOO' => 'bar.BARBAR');
    $this->assertEquals($expected, $this->rmap->getColumnMappings(), 'getColumnMappings() returns an associative array of column mappings');
  }
}
