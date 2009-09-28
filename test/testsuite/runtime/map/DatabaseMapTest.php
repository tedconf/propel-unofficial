<?php

require_once 'PHPUnit/Framework/TestCase.php';
include_once 'propel/map/ColumnMap.php';
include_once 'propel/map/TableMap.php';
include_once 'propel/map/DatabaseMap.php';

class TestDatabaseBuilder
{
  protected static $dmap = null;
  protected static $tmap = null;
  public static function getDmap()
  {
    if (is_null(self::$dmap)) {
        self::$dmap = new DatabaseMap('foodb');
    }
    return self::$dmap;
  }
  public static function setTmap($tmap)
  {
    self::$tmap = $tmap;
  }
  public static function getTmap()
  {
    return self::$tmap;
  }    
}

class BazTableMap extends TableMap
{
  public function initialize()
  {
    $this->setName('baz');
    $this->setPhpName('Baz');
  }
}

/**
 * Test class for DatabaseMap.
 *
 * @author     François Zaninotto
 * @version    $Id: ColumnMapTest.php 1121 2009-09-14 17:20:11Z francois $
 * @package    runtime.map
 */
class DatabaseMapTest extends PHPUnit_Framework_TestCase 
{ 
  protected $databaseMap;

  protected function setUp()
  {
    parent::setUp();
    $this->databaseName = 'foodb';
    $this->databaseMap = TestDatabaseBuilder::getDmap();
  }

  protected function tearDown()
  {
    // nothing to do for now
    parent::tearDown();
  }

  public function testConstructor()
  {
    $this->assertEquals($this->databaseName, $this->databaseMap->getName(), 'constructor sets the table name');
  }

  public function testAddTable()
  {
    $this->assertFalse($this->databaseMap->hasTable('foo'), 'tables are empty by default');
    try
    {
      $this->databaseMap->getTable('foo');
      $this->fail('getTable() throws an exception when called on an inexistent table');
    } catch(PropelException $e) {
      $this->assertTrue(true, 'getTable() throws an exception when called on an inexistent table');
    }
    $tmap = $this->databaseMap->addTable('foo');
    $this->assertTrue($this->databaseMap->hasTable('foo'), 'hasTable() returns true when the table was added by way of addTable()');
    $this->assertEquals($tmap, $this->databaseMap->getTable('foo'), 'getTable() returns a table by name when the table was added by way of addTable()');
  }
  
  public function testAddTableObject()
  {
    $this->assertFalse($this->databaseMap->hasTable('foo2'), 'tables are empty by default');
    try
    {
      $this->databaseMap->getTable('foo2');
      $this->fail('getTable() throws an exception when called on a table with no builder');
    } catch(PropelException $e) {
      $this->assertTrue(true, 'getTable() throws an exception when called on a table with no builder');
    }
    $tmap = new TableMap('foo2');
    $this->databaseMap->addTableObject($tmap);
    $this->assertTrue($this->databaseMap->hasTable('foo2'), 'hasTable() returns true when the table was added by way of addTableObject()');
    $this->assertEquals($tmap, $this->databaseMap->getTable('foo2'), 'getTable() returns a table by name when the table was added by way of addTableObject()');
  }

  public function testAddTableFromMapClass()
  {
    $table1 = $this->databaseMap->addTableFromMapClass('BazTableMap');
    try
    {
      $table2 = $this->databaseMap->getTable('baz');
      $this->assertEquals($table1, $table2, 'addTableFromMapClass() adds a table from a map class');
    } catch(PropelException $e) {
      $this->fail('addTableFromMapClass() adds a table from a map class');
    }
  }

  public function testGetColumn()
  {
    try
    {
      $this->databaseMap->getColumn('foo.BAR');
      $this->fail('getColumn() throws an exception when called on column of an inexistent table');
    } catch(PropelException $e) {
      $this->assertTrue(true, 'getColumn() throws an exception when called on column of an inexistent table');
    }
    $tmap = $this->databaseMap->addTable('foo');
    try
    {
      $this->databaseMap->getColumn('foo.BAR');
      $this->fail('getColumn() throws an exception when called on an inexistent column of an existent table');
    } catch(PropelException $e) {
      $this->assertTrue(true, 'getColumn() throws an exception when called on an inexistent column of an existent table');
    }
    $column = $tmap->addColumn('BAR', 'Bar', 'INTEGER');
    $this->assertEquals($column, $this->databaseMap->getColumn('foo.BAR'), 'getColumn() returns a ColumnMap object based on a fully qualified name');
  }
  
  public function testGetTableByPhpName()
  {
    try
    {
      $this->databaseMap->getTableByPhpName('Foo1');
      $this->fail('getTableByPhpName() throws an exception when called on an inexistent table');
    } catch(PropelException $e) {
      $this->assertTrue(true, 'getTableByPhpName() throws an exception when called on an inexistent table');
    }
    $tmap = $this->databaseMap->addTable('foo1');
    try
    {
      $this->databaseMap->getTableByPhpName('Foo1');
      $this->fail('getTableByPhpName() throws an exception when called on a table with no phpName');
    } catch(PropelException $e) {
      $this->assertTrue(true, 'getTableByPhpName() throws an exception when called on a table with no phpName');
    }
    $tmap2 = new TableMap('foo2');
    $tmap2->setPhpName('Foo2');
    $this->databaseMap->addTableObject($tmap2);
    $this->assertEquals($tmap2, $this->databaseMap->getTableByPhpName('Foo2'), 'getTableByPhpName() returns tableMap when phpName was set by way of TableMap::setPhpName()');
  }
    
  public function testGetTableByPhpNameNotLoaded()
  {
    $this->assertEquals('book', Propel::getDatabaseMap('bookstore')->getTableByPhpName('Book')->getName(), 'getTableByPhpName() can autoload a TableMap when the Peer class is generated and autoloaded');
  }
  
}
