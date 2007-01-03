<?php

require_once 'classes/propel/BaseTestCase.php';
include_once 'propel/util/Query.php';
include_once 'propel/util/BasePeer.php';
include_once 'propel/adapter/DBNone.php';

/**
 * Test class for Query.
 *
 * @author <a href="mailto:celkins@scardini.com">Christopher Elkins</a>
 * @author <a href="mailto:sam@neurogrid.com">Sam Joseph</a>
 * @version $Id$
 */
class QueryTest extends BaseTestCase {

	const DATABASE_NAME = "TESTDB";

	/** The criteria to use in the test. */
	private $c;

	private $dbMap;

	/**
	 * Initializes the criteria.
	 */
	public function setUp()
	{
		parent::setUp();


		$db = new DatabaseMap("TESTDB");

		$t1 = $db->addTable("test1");
		$t1->addPrimaryKey("id", "Id", PropelColumnTypes::INTEGER, true);
		$t1->addColumn("name", "Name", PropelColumnTypes::VARCHAR, true, 255);

		$invc = $db->addTable("invoice");
		$invc->addPrimaryKey("id", "Id", PropelColumnTypes::INTEGER, true);
		$invc->addColumn("cost", "Cost", PropelColumnTypes::DECIMAL, true);

		/*
		public function addColumn($name, $phpName, $type, $isNotNull = false, $size = null, $pk = null, $fkTable = null, $fkColumn = null)
		public function addForeignKey($columnName, $phpName, $type, $fkTable, $fkColumn, $isNotNull = false, $size = 0)
		public function addPrimaryKey($columnName, $phpName, $type, $isNotNull = false, $size = null)
		*/

		$this->dbMap = $db;

		Propel::registerDatabaseMap(self::DATABASE_NAME, $db);
		Propel::registerAdapter(self::DATABASE_NAME, new DBNone());
	}

	protected function createQuery($tableName, $alias = null)
	{
		return new Query($this->createCriteria($tableName, $alias));
	}

	protected function createCriteria($tableName, $alias = null)
	{
		return new Criteria(new QueryTable($this->dbMap->getTable($tableName), $alias));
	}

	/**
	 * Get an old-style, simplified bind params.
	 * @return array
	 */
	protected function getSimplifiedBindParams($bindParams)
	{
		$simple = array();
		foreach($bindParams as $colval) {
			$simple[] = array('table' => $colval->getColumnMap()->getTable()->getName(), 'column' => $colval->getColumnMap()->getName(), 'value' => $colval->getValue());
		}
		return $simple;
	}

	public function testNestedQuery()
	{
		print "Hello!\n";
		$q = $this->createQuery("test1", "test1_2");
		$q->addSelectColumn($q->getQueryTable()->createQueryColumn("name"));

		//var_dump($q->getQueryTable()->createQueryColumn("name"));

		$c = $this->createCriteria("test1");
		$c->add(new InExpr("id", $q));

		$params = array();
		$result = $c->buildSql($params);

		var_dump($result);
		//var_dump($this->getSimplifiedBindParams($params));

	}

	public function testCountAster()
	{
		/*
		$this->c = new Criteria();
		$this->c->addSelectColumn("COUNT(*)");
		$this->c->add("TABLE.TIME_COLUMN", Criteria::CURRENT_TIME);
		$this->c->add("TABLE.DATE_COLUMN", Criteria::CURRENT_DATE);

		$expect = "SELECT COUNT(*) FROM TABLE WHERE TABLE.TIME_COLUMN=CURRENT_TIME AND TABLE.DATE_COLUMN=CURRENT_DATE";

		$result = null;
		try {
			$result = BasePeer::createSelectSql($this->c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}

		$this->assertEquals($expect, $result);
		*/
	}

	public function testJoinObject ()
	{
		/*
		$j = new Join('TABLE_A.COL_1', 'TABLE_B.COL_2');
		$this->assertEquals(null, $j->getJoinType());
		$this->assertEquals('TABLE_A.COL_1', $j->getLeftColumn());
		$this->assertEquals('TABLE_A', $j->getLeftTableName());
		$this->assertEquals('COL_1', $j->getLeftColumnName());
		$this->assertEquals('TABLE_B.COL_2', $j->getRightColumn());
		$this->assertEquals('TABLE_B', $j->getRightTableName());
		$this->assertEquals('COL_2', $j->getRightColumnName());

		$j = new Join('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::LEFT_JOIN);
		$this->assertEquals('LEFT JOIN', $j->getJoinType());
		$this->assertEquals('TABLE_A.COL_1', $j->getLeftColumn());
		$this->assertEquals('TABLE_B.COL_1', $j->getRightColumn());

		$j = new Join('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::RIGHT_JOIN);
		$this->assertEquals('RIGHT JOIN', $j->getJoinType());
		$this->assertEquals('TABLE_A.COL_1', $j->getLeftColumn());
		$this->assertEquals('TABLE_B.COL_1', $j->getRightColumn());

		$j = new Join('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::INNER_JOIN);
		$this->assertEquals('INNER JOIN', $j->getJoinType());
		$this->assertEquals('TABLE_A.COL_1', $j->getLeftColumn());
		$this->assertEquals('TABLE_B.COL_1', $j->getRightColumn());
		*/
	}

	public function testAddingJoin ()
	{
		/*
		$c = new Criteria();
		$c->addSelectColumn("*");
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_1'); // straight join

		$expect = "SELECT * FROM TABLE_A, TABLE_B WHERE TABLE_A.COL_1=TABLE_B.COL_1";
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
		*/
	}

	public function testAddingMultipleJoins ()
	{
		/*
		$c = new Criteria();
		$c->addSelectColumn("*");
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_1');
		$c->addJoin('TABLE_B.COL_X', 'TABLE_D.COL_X');

		$expect = 'SELECT * FROM TABLE_A, TABLE_B, TABLE_D '
				 .'WHERE TABLE_A.COL_1=TABLE_B.COL_1 AND TABLE_B.COL_X=TABLE_D.COL_X';
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
		*/
	}

	public function testAddingLeftJoin ()
	{
		/*
		$c = new Criteria();
		$c->addSelectColumn("TABLE_A.*");
		$c->addSelectColumn("TABLE_B.*");
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_2', Criteria::LEFT_JOIN);

		$expect = "SELECT TABLE_A.*, TABLE_B.* FROM TABLE_A LEFT JOIN TABLE_B ON (TABLE_A.COL_1=TABLE_B.COL_2)";
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
		*/
	}

	public function testAddingMultipleLeftJoins ()
	{
		/*
		// Fails.. Suspect answer in the chunk starting at BasePeer:605
		$c = new Criteria();
		$c->addSelectColumn('*');
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::LEFT_JOIN);
		$c->addJoin('TABLE_A.COL_2', 'TABLE_C.COL_2', Criteria::LEFT_JOIN);

		$expect = 'SELECT * FROM TABLE_A '
				 .'LEFT JOIN TABLE_B ON (TABLE_A.COL_1=TABLE_B.COL_1) '
						 .'LEFT JOIN TABLE_C ON (TABLE_A.COL_2=TABLE_C.COL_2)';
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
		*/
	}

	public function testAddingRightJoin ()
	{
		/*
		$c = new Criteria();
		$c->addSelectColumn("*");
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_2', Criteria::RIGHT_JOIN);

		$expect = "SELECT * FROM TABLE_A RIGHT JOIN TABLE_B ON (TABLE_A.COL_1=TABLE_B.COL_2)";
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
		*/
	}

	public function testAddingMultipleRightJoins ()
	{
		/*
		// Fails.. Suspect answer in the chunk starting at BasePeer:605
		$c = new Criteria();
		$c->addSelectColumn('*');
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::RIGHT_JOIN);
		$c->addJoin('TABLE_A.COL_2', 'TABLE_C.COL_2', Criteria::RIGHT_JOIN);

		$expect = 'SELECT * FROM TABLE_A '
				 .'RIGHT JOIN TABLE_B ON (TABLE_A.COL_1=TABLE_B.COL_1) '
						 .'RIGHT JOIN TABLE_C ON (TABLE_A.COL_2=TABLE_C.COL_2)';
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
		*/
	}

	public function testAddingInnerJoin ()
	{
		/*
		$c = new Criteria();
		$c->addSelectColumn("*");
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::INNER_JOIN);

		$expect = "SELECT * FROM TABLE_A INNER JOIN TABLE_B ON (TABLE_A.COL_1=TABLE_B.COL_1)";
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
		*/
	}

	public function testAddingMultipleInnerJoin ()
	{
		/*
		$c = new Criteria();
		$c->addSelectColumn("*");
		$c->addJoin('TABLE_A.COL_1', 'TABLE_B.COL_1', Criteria::INNER_JOIN);
		$c->addJoin('TABLE_B.COL_1', 'TABLE_C.COL_1', Criteria::INNER_JOIN);

		$expect = 'SELECT * FROM TABLE_A '
				 .'INNER JOIN TABLE_B ON (TABLE_A.COL_1=TABLE_B.COL_1) '
						 .'INNER JOIN TABLE_C ON (TABLE_B.COL_1=TABLE_C.COL_1)';
		try {
			$result = BasePeer::createSelectSql($c, $params=array());
		} catch (PropelException $e) {
			print $e->getTraceAsString();
			$this->fail("PropelException thrown in BasePeer.createSelectSql(): ". $e->getMessage());
		}
		$this->assertEquals($expect, $result);
		*/
	}
}
