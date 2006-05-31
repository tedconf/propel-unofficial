<?php
/*
 *  $Id: Criteria.php 372 2006-05-25 21:14:17Z hans $
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

require_once 'propel/util/criteria/Expression.php';
require_once 'propel/util/criteria/ExpressionContainer.php';
require_once 'propel/util/criteria/BaseExpressionContainer.php';
require_once 'propel/util/criteria/BaseExpression.php';
require_once 'propel/util/criteria/ColumnExpression.php';
require_once 'propel/util/criteria/ColumnValueExpression.php';
require_once 'propel/util/criteria/MultiValueExpression.php';
require_once 'propel/util/criteria/LogicExpression.php';

/**
 * This is a utility class for holding criteria information for a query.
 *
 * BasePeer constructs SQL statements based on the values in this class.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 *
 * @version $Revision: 372 $
 * @package propel.util
 */
class Criteria extends BaseExpressionContainer implements ExpressionContainer {

    /**
     * @var ContainerExpression (Defaults to AndExpr if none specified).
     */
    protected $container;

	/**
	 * @var Join[]
	 */
	protected $joins = array();

	/**
	 * @var Expression
	 */
	protected $having;

    /**
     * Construct a new Criteria instance for a specific table.
     */
    public function __construct(QueryTable $table)
    {
		$this->setQueryTable($table);
	}

    /**
     * Adds an Expression to this Criteria object.
     */
    public function add(Expression $expr)
    {
    	if ($expr->getIgnoreCase() === null) {
			$expr->setIgnoreCase($this->getIgnoreCase());
		}
		if ($expr->getQueryTable() === null) {
			$expr->setQueryTable($this->getQueryTable());
		}
    	if ($this->container === null) {
    		$this->container = new AndExpr();
    	}
		$this->container->add($expr);
		return $this;
	}

	public function buildSql(&$sql, &$values)
	{
		return $this->container->buildSql($sql, $values);
	}

	/**
	 * Return the Iterator (IteratorAggregate itnerface).
	 */
	public function getIterator()
	{
		if ($this->container) {
			return $this->container->getIterator();
		} else {
			return new ArrayIterator();
		}
	}
	
	/**
	 * This provides the database key based used by the table in this criteria.
	 * 
	 * Since all Criteria must use the same database, we're not going to worry about
	 * issues related to nested Criteria potentially having different database names.  If 
	 * they do, the query will certainly blow up soon enough :)
	 * 
	 * This is used by BasePeer to load up a DBAdapter and DatabaseMap objects.  We may
	 * want to have those objects loaded directly from the Criteria, but at this point,
	 * this is the not-very-efficient, but simpler solution.
	 * 
	 * @return strin
	 */
	public function getDbName()
	{
		return $this->getQueryTable()->getTableMap()->getDatabase()->getName();
	}
}



class EqualExpr extends ColumnValueExpression {

	protected function getOperator()
	{
		return "=";
	}

	protected function getNullValueSql()
	{
		return $this->getQueryColumn()->getQualifiedSql() . " IS NULL";
	}

}


class NotEqualExpr extends ColumnValueExpression {

	protected function getOperator()
	{
		return "!=";
	}

	protected function getNullValueSql()
	{
		return $this->getQueryColumn()->getQualifiedSql() . " IS NOT NULL";
	}

}

class GreaterExpr extends ColumnValueExpression {

	protected function getOperator()
	{
		return ">";
	}
}

class GreaterEqualExpr extends ColumnValueExpression {

	protected function getOperator()
	{
		return ">=";
	}
}

class LessExpr extends ColumnValueExpression {

	protected function getOperator()
	{
		return "<";
	}
}

class LessEqualExpr extends ColumnValueExpression {

	protected function getOperator()
	{
		return "<=";
	}
}

class LikeExpr extends ColumnValueExpression {

	protected function getIgnoreCaseSql()
	{
		$op = $this->getOperator();
		$adapter = $this->getAdapter();
		if ($adapter instanceof DBPostgres) {
			$op = "ILIKE";
			return $this->getColname() . " " . $op . " ?";
		} else {
			$col = $this->getColumn();
			return $col->ignoreCase($this->getColname()) . $op . $col->ignoreCase("?");
		}
	}

	protected function getOperator()
	{
		return "LIKE";
	}
}

class NotLikeExpr extends ColumnValueExpression {

	protected function getIgnoreCaseSql()
	{
		$op = $this->getOperator();
		$adapter = $this->getAdapter();
		if ($adapter instanceof DBPostgres) {
			$op = "NOT ILIKE";
			return $this->getColname() . " " . $op . " ?";
		} else {
			$col = $this->getColumn();
			return $col->ignoreCase($this->getColname()) . $op . $col->ignoreCase("?");
		}
	}

	protected function getOperator()
	{
		return "NOT LIKE";
	}
}

class InExpr extends MultiValueExpression {

	 protected function getEmptyValuesSql()
	 {
	 	return "1<>1";
	 }

	protected function getOperator()
	{
		return "IN";
	}
}

class NotInExpr extends MultiValueExpression {

	 protected function getEmptyValuesSql()
	 {
	 	return "1=1";
	 }

	protected function getOperator()
	{
		return "NOT IN";
	}
}


class AndExpr extends LogicExpression {

	public function getOperator()
	{
		return "AND";
	}

}

class OrExpr extends LogicExpression {

	public function getOperator()
	{
		return "OR";
	}

}

/**
 * An expression containing custom SQL.
 * 
 * This can be used a simple container for custom SQL (e.g. passed as a value
 * to another expression) or it can be used as a standalone Expression (e.g.
 * added to a Criteria).
 * 
 */
class SqlExpr extends BaseExpression implements Expression {

	private $sql;
	
	/**
	 * Constructs a new object with custom SQL.
	 * @param string $sql
	 */
	public function __construct($sql)
	{
		$this->sql = $sql;
	}
	
	public function buildSql(&$sql, &$values)
	{
		$sql .= $this->sql;
	}
	
	public function getSql()
	{
		return $this->sql;
	}
	
}

/**
 * A class to hold a column-value pair.
 * 
 * This is used by the Expression#buildSql() method to add values to the passed-in array, and
 * then by BasePeer::populateStmtValues2() to bind values to PDO statements.
 * 
 */
class ColumnValue {

	private $column;
	private $value;

	/**
	 *
	 * @param ColumnMap $col
	 * @param mixed $value
	 */
	public function __construct(ColumnMap $col, $value)
	{
		$this->column = $col;
		$this->value = $value;
	}

	public function getColumnMap()
	{
		return $this->column;
	}

	public function setColumnMap(ColumnMap $col)
	{
		$this->column = $col;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($val)
	{
		$this->value = $val;
	}

}

/**
 * This class represents a collection of ColumnValue objects for a single table.
 * 
 * This class is used by the doInsert() methods to store a collection of ColumnValue
 * objects for insertion.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class ColumnValueCollection implements IteratorAggregate {

	private $tableMap;
	private $columnValues = array();
	
	
	/**
	 * Constructs a new collection for the specified table.
	 * @param TableMap $table A TableMap
	 */
	public function __construct(TableMap $table)
	{
		$this->tableMap = $table;
	}
	
	public function add(ColumnValue $cv)
	{
		$this->columnValues[$cv->getColumnMap()->getName()] = $cv;
	}
	
	/**
	 * Creates the QueryColumn and then ColumnValue objects and adds to collection.
	 * @param string $colname The name of the column.
	 * @param mixed $value The value.
	 */ 
	public function set($colname, $value)
	{
		$colMap = $this->tableMap->getColumn($colname);
		if (!$colMap) {
			throw new PropelException("Unable to load ColumnMap for column [" . $colname . "]");
		}
		$this->columnValues[$colname] = new ColumnValue($colMap, $value); // we could call ->add() but this is a tad quicker
	}
	
	public function get($key)
	{
		if (!isset($this->columnValues[$key])) {
			return null;
		}
		return $this->columnValues[$key];
	}
	
	public function remove($key)
	{
		if (isset($this->columnValues[$key])) {
			unset($this->columnValues[$key]);
		}
	}
	
	public function keys()
	{
		return array_keys($this->columnValues);
	}
	
	public function containsKey($key)
	{
		return array_key_exists($key, $this->columnValues);
	}
	
	public function size()
	{
		return count($this->columnValues);
	}
	
	public function getTableMap()
	{
		return $this->tableMap;
	}
	
	/**
	 * SPL IteratorAggregate method to return an Iterator for this object.
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new ColumnValueCollectionIterator($this);
	}
}

/**
 * Class that implements SPL Iterator interface for iterating over a ColumnValueCollection. 
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.util
 */
class ColumnValueCollectionIterator implements Iterator {

    private $idx = 0;
    private $criteria;
    private $criteriaKeys;
    private $criteriaSize;
    
    public function __construct(ColumnValueCollection $coll) {
        $this->coll = $coll;
        $this->keys = $coll->keys();
        $this->size = $coll->size();
    }

    public function rewind() {
        $this->idx = 0;
    }
    
    public function valid() {
        return $this->idx < $this->size;
    }
    
    public function key() {
        return $this->keys[$this->idx];
    }
    
    public function current() {
        return $this->coll->get($this->keys[$this->idx]);
    }
    
    public function next() {
        $this->idx++;
    }
    
    public function size()
	{
    	return $this->size;
    }

}
