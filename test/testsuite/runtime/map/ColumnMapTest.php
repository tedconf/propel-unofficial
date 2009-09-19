<?php

require_once 'PHPUnit/Framework/TestCase.php';
include_once 'propel/map/ColumnMap.php';
include_once 'propel/map/TableMap.php';

class FakeTableBuilder implements MapBuilder
{
  public function doBuild()
  {
  }
  
  public function isBuilt()
  {
    return true;
  }

  public function getDatabaseMap()
  {
  }
}

/**
 * Test class for TableMap.
 *
 * @author     François Zaninotto
 * @version    $Id: ColumnMapTest.php 1121 2009-09-14 17:20:11Z francois $
 * @package    runtime.map
 */
class ColumnMapTest extends PHPUnit_Framework_TestCase 
{ 
  protected $databaseMap;

  protected function setUp()
  {
    parent::setUp();
    $this->dmap = new DatabaseMap('foodb');
    $this->tmap = new TableMap('foo', $this->dmap);
    $this->columnName = 'bar';
    $this->cmap = new ColumnMap($this->columnName, $this->tmap);
  }

  protected function tearDown()
  {
    // nothing to do for now
    parent::tearDown();
  }

  public function testConstructor()
  {
    $this->assertEquals($this->columnName, $this->cmap->getName(), 'constructor sets the column name');
    $this->assertEquals($this->tmap, $this->cmap->getTable(), 'Constructor sets the table map');
    $this->assertNull($this->cmap->getType(), 'A new column map has no type');
  }
  
  public function testPhpName()
  {
    $this->assertNull($this->cmap->getPhpName(), 'phpName is empty until set');
    $this->cmap->setPhpName('FooBar');
    $this->assertEquals('FooBar', $this->cmap->getPhpName(), 'phpName is set by setPhpName()');
  }
  
  public function testType()
  {
    $this->assertNull($this->cmap->getType(), 'type is empty until set');
    $this->cmap->setType('FooBar');
    $this->assertEquals('FooBar', $this->cmap->getType(), 'type is set by setType()');
  }
  
  public function tesSize()
  {
    $this->assertEquals(0, $this->cmap->getSize(), 'size is empty until set');
    $this->cmap->setSize(123);
    $this->assertEquals(123, $this->cmap->getSize(), 'size is set by setSize()');
  }
  
  public function testPrimaryKey()
  {
    $this->assertFalse($this->cmap->isPrimaryKey(), 'primaryKey is false by default');
    $this->cmap->setPrimaryKey(true);
    $this->assertTrue($this->cmap->isPrimaryKey(), 'primaryKey is set by setPrimaryKey()');
  }
  
  public function testNotNull()
  {
    $this->assertFalse($this->cmap->isNotNull(), 'notNull is false by default');
    $this->cmap->setNotNull(true);
    $this->assertTrue($this->cmap->isNotNull(), 'notNull is set by setPrimaryKey()');
  }
  
  public function testDefaultValue()
  {
    $this->assertNull($this->cmap->getDefaultValue(), 'defaultValue is empty until set');
    $this->cmap->setDefaultValue('FooBar');
    $this->assertEquals('FooBar', $this->cmap->getDefaultValue(), 'defaultValue is set by setDefaultValue()');
  }
  
  public function testGetForeignKey()
  {
    $this->assertFalse($this->cmap->isForeignKey(), 'foreignKey is false by default');
    try
    {
      $this->cmap->getRelatedTable();
      $this->fail('getRelatedTable throws an exception when called on a column with no foreign key');
    } catch(PropelException $e) {
      $this->assertTrue(true, 'getRelatedTable throws an exception when called on a column with no foreign key');
    }
    try
    {
      $this->cmap->getRelatedColumn();
      $this->fail('getRelatedColumn throws an exception when called on a column with no foreign key');
    } catch(PropelException $e) {
      $this->assertTrue(true, 'getRelatedColumn throws an exception when called on a column with no foreign key');
    }
    $relatedTmap = $this->dmap->addTable('foo2');
    // required to let the database map use the foreign TableMap
    $this->dmap->addTableBuilder('foo2', new FakeTableBuilder());
    $relatedCmap = $relatedTmap->addColumn('BAR2', 'Bar2', 'INTEGER');
    $this->cmap->setForeignKey('foo2', 'BAR2');
    $this->assertTrue($this->cmap->isForeignKey(), 'foreignKey is true after setting the foreign key via setForeignKey()');
    $this->assertEquals($relatedTmap, $this->cmap->getRelatedTable(), 'getRelatedTable returns the related TableMap object');
    $this->assertEquals($relatedCmap, $this->cmap->getRelatedColumn(), 'getRelatedColumn returns the related ColumnMap object');
  }
  
  public function testNormalizeName()
  {
    $this->assertEquals('', ColumnMap::normalizeName(''), 'normalizeColumnName() returns an empty string when passed an empty string');
    $this->assertEquals('BAR', ColumnMap::normalizeName('bar'), 'normalizeColumnName() uppercases the input');
    $this->assertEquals('BAR_BAZ', ColumnMap::normalizeName('bar_baz'), 'normalizeColumnName() does not mind underscores');
    $this->assertEquals('BAR', ColumnMap::normalizeName('FOO.BAR'), 'normalizeColumnName() removes table prefix');
    $this->assertEquals('BAR', ColumnMap::normalizeName('BAR'), 'normalizeColumnName() leaves normalized column names unchanged');
    $this->assertEquals('BAR_BAZ', ColumnMap::normalizeName('foo.bar_baz'), 'normalizeColumnName() can do all the above at the same time');
  }

}
