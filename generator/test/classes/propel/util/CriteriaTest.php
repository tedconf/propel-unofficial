<?php

require_once 'classes/propel/BaseTestCase.php';
include_once 'propel/util/Criteria.php';
include_once 'propel/util/BasePeer.php';
include_once 'propel/adapter/DBNone.php';

/**
 * Test class for Criteria.
 *
 * @author <a href="mailto:celkins@scardini.com">Christopher Elkins</a>
 * @author <a href="mailto:sam@neurogrid.com">Sam Joseph</a>
 * @version $Id$
 */
class CriteriaTest extends BaseTestCase {
	
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
        
        
        $db = new DatabaseMap(self::DATABASE_NAME);
		  
        $t1 = $db->addTable("test1"); 
        $t1->addPrimaryKey("id", "Id", PropelColumnTypes::INTEGER, true);
        $t1->addColumn("name", "Name", PropelColumnTypes::VARCHAR, true, 255);
        $t1->addColumn("active", "Active", PropelColumnTypes::BOOLEAN, false);
        
        $mt2 = $db->addTable("myTable2");
        $mt2->addColumn("myColumn2", "Mycolumn2", PropelColumnTypes::VARCHAR, true, 255);
        
        $mt3 = $db->addTable("myTable3");
        $mt3->addColumn("myColumn3", "Mycolumn3", PropelColumnTypes::VARCHAR, true, 255);
        
        $mt4 = $db->addTable("myTable4");
        $mt4->addColumn("myColumn4", "Mycolumn4", PropelColumnTypes::VARCHAR, true, 255);
        
        $mt5 = $db->addTable("myTable5");
        $mt5->addColumn("myColumn5", "Mycolumn5", PropelColumnTypes::VARCHAR, true, 255);
        
        $invc = $db->addTable("invoice"); 
        $invc->addPrimaryKey("id", "Id", PropelColumnTypes::INTEGER, true);        
        $invc->addColumn("cost", "Cost", PropelColumnTypes::DECIMAL, true);
        $invc->addColumn("product_name", "ProductName", PropelColumnTypes::VARCHAR, true, 255);
        
        /*
		public function addColumn($name, $phpName, $type, $isNotNull = false, $size = null, $pk = null, $fkTable = null, $fkColumn = null)
        public function addForeignKey($columnName, $phpName, $type, $fkTable, $fkColumn, $isNotNull = false, $size = 0)
		public function addPrimaryKey($columnName, $phpName, $type, $isNotNull = false, $size = null)
		*/
		
		$this->dbMap = $db;
		Propel::registerDatabaseMap(self::DATABASE_NAME, $db);
		Propel::registerAdapter(self::DATABASE_NAME, new DBNone());
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
	
	
	
    /**
     * test various properties of Criterion and nested criterion
     */
    public function testNestedCriterion()
    {
        $table2 = "myTable2";
        $column2 = "myColumn2";
        $value2 = "myValue2";

        $table3 = "myTable3";
        $column3 = "myColumn3";
        $value3 = "myValue3";


        $table4 = "myTable4";
        $column4 = "myColumn4";
        $value4 = "myValue4";

        $table5 = "myTable5";
        $column5 = "myColumn5";
        $value5 = "myValue5";
		
		$expr2 = new EqualExpr($column2, $value2);
		$expr2->setQueryTable(new QueryTable($this->dbMap->getTable($table2)));
		
		$expr3 = new EqualExpr($column3, $value3);
		$expr3->setQueryTable(new QueryTable($this->dbMap->getTable($table3)));
		
		$expr4 = new EqualExpr($column4, $value4);
		$expr4->setQueryTable(new QueryTable($this->dbMap->getTable($table4)));
		
		$expr5 = new EqualExpr($column5, $value5);
		$expr5->setQueryTable(new QueryTable($this->dbMap->getTable($table5)));
		
		$c2 = $this->createCriteria($table2);
		
		$c2->add(new OrExpr(new AndExpr($expr2, $expr3), new AndExpr($expr4, $expr5)));		
        
        $expect =
			"((myTable2.myColumn2 = ? "
                . "AND myTable3.myColumn3 = ?) "
            . "OR (myTable4.myColumn4 = ? "
                . "AND myTable5.myColumn5 = ?))";
		
		
		
		$params = array();
        $result = $c2->buildSql($params);

        $expect_params = array(
                                    array('table' => 'myTable2', 'column' => 'myColumn2', 'value' => 'myValue2'),
                                    array('table' => 'myTable3', 'column' => 'myColumn3', 'value' => 'myValue3'),
                                    array('table' => 'myTable4', 'column' => 'myColumn4', 'value' => 'myValue4'),
                                    array('table' => 'myTable5', 'column' => 'myColumn5', 'value' => 'myValue5'),
                                );
		
        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $this->getSimplifiedBindParams($params));

    }

    /**
     * Tests &lt;= and =&gt;.
     */
    public function testBetweenCriterion()
    {
    	//$tmap = $this->dbMap->getTable("invoice");
    	$c = $this->createCriteria("invoice");
    	
    	$expr1 = new GreaterEqualExpr("cost", 1000);
    	$expr2 = new LessEqualExpr("cost", 5000);
    	
        $c->add($expr1);
        $c->add($expr2);
                
        $expect = "(invoice.cost >= ? AND invoice.cost <= ?)";

        $expect_params = array( array('table' => 'invoice', 'column' => 'cost', 'value' => 1000),
                                array('table' => 'invoice', 'column' => 'cost', 'value' => 5000),
                               );

        try {
        	$params = array();
            $result = $c->buildSql($params);
            
        } catch (PropelException $e) {
            $this->fail("PropelException thrown in BasePeer.createSelectSql(): ".$e->getMessage());
        }
		
        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $this->getSimplifiedBindParams($params));
    }

    /**
     * Verify that AND and OR criterion are nested correctly.
     */
    public function testPrecedence()
    {	
    	$expr1 = new GreaterEqualExpr("cost", 1000);
    	$expr2 = new LessEqualExpr("cost", 2000);
    	$expr3 = new GreaterEqualExpr("cost", 8000);
    	$expr4 = new LessEqualExpr("cost", 9000);
    	
        $c = $this->createCriteria("invoice");
        
        $c->add(new OrExpr(new AndExpr($expr1, $expr2), new AndExpr($expr3, $expr4)));
        
        $expect = "((invoice.cost >= ? AND invoice.cost <= ?) OR (invoice.cost >= ? AND invoice.cost <= ?))";

        $expect_params = array( array('table' => 'invoice', 'column' => 'cost', 'value' => 1000),
                                array('table' => 'invoice', 'column' => 'cost', 'value' => 2000),
                                array('table' => 'invoice', 'column' => 'cost', 'value' => 8000),
                                array('table' => 'invoice', 'column' => 'cost', 'value' => 9000),
                               );

        try {
        	$params=array();
            $result = $c->buildSql($params);
        } catch (PropelException $e) {
        	print $e;
            $this->fail("PropelException thrown in BasePeer::createSelectSql()");
        }
		
        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $this->getSimplifiedBindParams($params));
    }

    /**
     * Test Criterion.setIgnoreCase().
     * As the output is db specific the test just prints the result to
     * System.out
     */
    public function testExpressionIgnoreCase()
    {
    	
    	
    	$c = $this->createCriteria("invoice");
    	
        $expr = new LikeExpr("product_name", "FoObAr");
        $expr->setQueryTable(new QueryTable($this->dbMap->getTable("invoice")));
        
        $result = $expr->buildSql($params);
		$this->assertEquals("invoice.product_name LIKE ?", $result);
		
		// now set to ignore case
		
		$expr->setIgnoreCase(true);
		include_once 'propel/adapter/DBPostgres.php';
		Propel::registerAdapter(self::DATABASE_NAME, new DBPostgres());
		        
		$result = $expr->buildSql($params);
		$this->assertEquals("invoice.product_name ILIKE ?", $result);
		
		// MySQL is not case-sensitive in LIKE comparisons
		include_once 'propel/adapter/DBMySQL.php';
		Propel::registerAdapter(self::DATABASE_NAME, new DBMySQL());
		
		$result = $expr->buildSql($params);
		$this->assertEquals("invoice.product_name LIKE ?", $result);
		
		include_once 'propel/adapter/DBSQLite.php';
		Propel::registerAdapter(self::DATABASE_NAME, new DBSQLite());
		
		$result = $expr->buildSql($params);
		$this->assertEquals("UPPER(invoice.product_name) LIKE UPPER(?)", $result);
    }

    /**
     * Test that true is evaluated correctly.
     */
    public function testBoolean()
    {
        $c = $this->createCriteria("test1");
        
        $c->add(new EqualExpr("active", true));
        

        $expect = "test1.active = ?";
        $expect_params = array( array('table' => 'test1', 'column' => 'active', 'value' => true));
        
    
    	$params = array();
        $result = $c->buildSql($params);
        
        $this->assertEquals($expect, $result);
        $this->assertEquals($expect_params, $this->getSimplifiedBindParams($params));

    }
    
	public function testIn()
	{	
		$params = array();
		$c = $this->createCriteria("invoice");
		
		$c->add(new InExpr("product_name", array()));
		$c->add(new InExpr("id", array(1,2,3)));
		
		$expect = "(1<>1 AND invoice.id IN (?,?,?))";
		
        $result = $c->buildSql($params);
        
		$this->assertEquals($expect, $result);
		
		
		$c->add(new InExpr("id", null));
		
		$expect = "(1<>1 AND invoice.id IN (?,?,?) AND invoice.id IS NULL)";
		$params = array();
		$result = $c->buildSql($params);
        

		$this->assertEquals($expect, $result);

		$expect_params = array( array('table' => 'invoice', 'column' => 'id', 'value' => 1),
                                array('table' => 'invoice', 'column' => 'id', 'value' => 2),
                                array('table' => 'invoice', 'column' => 'id', 'value' => 3),
                               );
		
		$this->assertEquals($expect_params, $this->getSimplifiedBindParams($params));
				
	}
	
	public function testNotIn()
	{	
		$params = array();
		$c = $this->createCriteria("invoice");
		
		$c->add(new NotInExpr("product_name", array()));
		$c->add(new NotInExpr("id", array(1,2,3)));
		$c->add(new NotInExpr("id", null));
		
		$expect = "(1=1 AND invoice.id NOT IN (?,?,?) AND invoice.id IS NOT NULL)";
		$params = array();
		$result = $c->buildSql($params);
        
//        var_dump($expect);
//        var_dump($result);
        
		$this->assertEquals($expect, $result);

		$expect_params = array( array('table' => 'invoice', 'column' => 'id', 'value' => 1),
                                array('table' => 'invoice', 'column' => 'id', 'value' => 2),
                                array('table' => 'invoice', 'column' => 'id', 'value' => 3),
                               );
		
		$this->assertEquals($expect_params, $this->getSimplifiedBindParams($params));
				
	}

}
