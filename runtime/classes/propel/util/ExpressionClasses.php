<?php
/*
 * $Id$
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

/**
 * This file contains a collection of classes and interfaces that repersent expressions.
 * 
 * The Expression classes form the basis of the Criteria system in Propel 2.0.
 */

// -----------------------------------------------------------------------------
// I N T E R F A C E S
// -----------------------------------------------------------------------------

/**
 * The Expression interface is the basic building block of expressions in Proel.
 */
interface Expression {

	/**
	 * Builds out the SQL for this expression, adding it to the passed in $sql param
	 * and adding any ColumnValue pairs to the passed-in $values array.
	 * @param &$values ColumnValue[] The array of ColumnValue objects that any values should be added to.
	 * @return string The built SQL  
	 */
	public function buildSql(&$params);

	/**
	 * Set whether this expression is case-insensitive.
	 * @param boolean $bit
	 */
	public function setIgnoreCase($bit);

	/**
	 * Returns whether this expression is case-insensitive.
	 * @return boolean
	 */
	public function getIgnoreCase();

	/**
	 * Sets the QueryTable for this expression.
	 * 
	 * The QueryTable is used when building out the SQL to prepend the appropriate
	 * prefixes (aliases), etc.
	 * 
	 * @param QueryTable $table
	 */
	public function setQueryTable(QueryTable $table);

	/**
	 * Gets the QueryTable for this expression.
	 * @return QueryTable
	 */
	public function getQueryTable();

}

// -----------------------------------------------------------------------------
// A B S T R A C T    I M P L E M E N T A T I O N   C L A S S E S
// -----------------------------------------------------------------------------

/**
 * The ExpressionContainer interface is the interface for expressions that can contain
 * other expressions.
 * 
 * The containment can imply logical relationships (@see LogicExpression) or simply a
 * collection of Expression elements. 
 */
interface ExpressionContainer extends Expression, IteratorAggregate {

	/**
	 * Adds an Expression to this container.
	 * @param Expression $e
	 */
	public function add(Expression $e);
}


/**
 * The abstract superclass for all basic expressions. 
 */
abstract class BaseExpression implements Expression {

	private $queryTable;
	private $tableAlias;
	private $ignoreCase;

	/**
	 * Sets the QueryTable for this expression.
	 * @param $table QueryTable
	 */
	public function setQueryTable(QueryTable $table)
	{
		$this->queryTable = $table;
	}

	/**
	 * Gets the QueryTable for this expression.
	 * @return QueryTable
	 */
	public function getQueryTable()
	{
		return $this->queryTable;
	}

	/**
     * Sets ignore case.
     *
     * @param boolean $b True if case should be ignored.
     * @return A modified Criteria object.
     */
    public function setIgnoreCase($b)
    {
        $this->ignoreCase = (boolean) $b;
        return $this;
    }

    /**
     * Get the ignore case value.
     *
     * @return boolean True if case is ignored, false otherwise, or NULL if not set.
     */
    public function getIgnoreCase()
    {
        return $this->ignoreCase;
    }

}

/**
 * The abstract superclass for all expressions that contain other expressions. 
 */ 
abstract class BaseExpressionContainer extends BaseExpression implements ExpressionContainer {

	/**
	 * Sets the QueryTable for this expression and all children (overriding any previous value they may have had).
	 * @param QueryTable $table
	 */
	public function setQueryTable(QueryTable $table)
	{
		parent::setQueryTable($table);
		foreach($this as $expr) {
			$expr->setQueryTable($table);
		}
	}

	/**
     * Sets ignore case for this expression and all children (overriding any previous value they may have had).
     *
     * @param boolean $b True if case should be ignored.
     * @return A modified ExpressionContainer object.
     */
    public function setIgnoreCase($b)
    {
    	$b = (boolean) $b;
        parent::setIgnoreCase($b);
        foreach($this as $expr) {
        	$expr->setIgnoreCase($b);
		}
        return $this;
    }
}


/**
 * The abstract superclass for expressions that contain a column.
 */
abstract class ColumnExpression extends BaseExpression {

	private $colname;
	
	/**
	 * Creates a new instance of this expression for a given column.
	 * @param string $colname The name of the column.  This is resolved later using
	 * 							the QueryTable for this Expression.
	 */
	protected function __construct($colname)
	{
		$this->colname = $colname;
	}

	/**
     * Gets the DBAdapter, which is looked-up based on current QueryTable.
     * @return DBAdapter
     * @throws PropelException if QueryTable was not set.
     */
    protected function getAdapter()
    {
    	$qt = $this->getQueryTable();
		if ($qt === null) {
			throw new PropelException("QueryTable must be set in Expression (setQueryTable(QueryTable)) before you can call getAdapter()");
		}
		return Propel::getDB($qt->getDbName());
	}

	/**
     * Gets the resolved column (resolved based on curent QueryTable).
     * 
     * This returns a new object every time it is called.
     * 
     * @param string $colname
     * @return ActualQueryColumn
     * @throws PropelException if TableMap was not set or column is invalid.
     */
    protected function createQueryColumn()
    {
    	$qt = $this->getQueryTable();
		if ($qt === null) {
			throw new PropelException("The QueryTable must be set in Expression (setTable(TableMap)) before you can evaluate it.");
		}
		return $qt->createQueryColumn($this->colname);
	}

}


/**
 * The abstract superclass for expressions that contain a column and a value.
 */
abstract class ColumnValueExpression extends ColumnExpression {

	protected $value;
	
	/**
	 * Construct a new expression with column name and value.
	 * @param string $colname
	 * @param mixed $value 	Value can be a simple scalar (in which case it is bound to the expression 
	 * 						later using PDOStatement->bindValue()) or it can be a SqlExpr object, 
	 * 						in which case the result of SqlExpr->getSql() is added as the value in the 
	 * 						buildSql() method.
	 */
	public function __construct($colname, $value)
	{
		parent::__construct($colname);
		$this->value = $value;
	}
	
	/**
	 * Gets the operator for this expression.
	 * 
	 * This method must be defined by the subclasses to return their operator.
	 * 
	 * @return string The operator for the expression.
	 */
	abstract protected function getOperator();
	
	/**
	 * @see Expression::buildSql()
	 */
	public function buildSql(&$params)
	{
		$col = $this->createQueryColumn();
		
		if ($this->value !== null) {
			
			// are we dealing with a traditional value or a SqlExpr ?
			if ($this->value instanceof SqlExpr) {
				// this is a SqlExpr, so the value is going to be the result of SqlExpr->getSql()
				
				// We're not going to honor the ignore-case setting for custom SQL							
				$sql = $col->getQualifiedSql() . ' ' . $this->getOperator() . ' ' . $this->value->getSql();
				
			} else {
			
				// default case, it is a normal col = value expression; value
				// will be replaced w/ '?' and will be inserted later using PDO bindValue()
				if ($this->getIgnoreCase() && $this->columnMap->isStringType()) {
					$sql = $this->getIgnoreCaseSql($col, $this->getAdapter());
			    } else {
		        	$sql = $col->getQualifiedSql() . ' ' . $this->getOperator() . ' ?';
		    	}
	
				// need to track the field in params, because
				// we'll need it to determine the correct setter
				// method later on (e.g. field 'review.DATE' => setDate());
				$params[] = new ColumnValue($col->getColumnMap(), $this->value);
			}
						
		} else {
			// value is null, which means it was either not specified or specifically
            // set to null.

			$sql = $this->getNullValueSql($col);
		}
		
		return $sql;
	}
	
	/**
	 * Gets the version of the SQL expression to use for a case-insensitive query.
	 * 
	 * In some cases this will be something like UPPER(col) = UPPER(?), but in others
	 * it may actually involve a slightly different expression -- e.g. case-insensitive
	 * version of "col LIKE ?" for PostgreSQL would be "col ILIKE ?".
	 * 
	 * @param QueryColumn $col The QueryColumn to use (passed-in to avoid needing to re-create that object).
	 * @param DBAdapter $db The Database Adapter (passed-in to avoid lookup).
	 */  
	protected function getIgnoreCaseSql(QueryColumn $col, DBAdapter $db)
	{
		return $db->ignoreCase($col->getQualifiedSql()) . ' ' . $this->getOperator() . ' ' . $db->ignoreCase('?');
	}

	/**
	 * Builds SQL if the value is NULL.
	 * 
	 * @param QueryColumn $col The QueryColumn to use (passed-in to avoid needing to re-create that object).
	 */
	protected function getNullValueSql(QueryColumn $col)
	{
		throw new PropelException("Could not build SQL for expression: " . $col->getQualifiedSql() . " ". $this->getOperator() . " NULL");
	}

}

/**
 * The abstract superclass for IN and NOT IN expressions.
 * 
 * IN and NOT IN expressions are expressions that contain columns (i.e. ColumnExpression)
 * but the values are arrays -- and hence more complex to build out with the correct
 * bind parameters.
 */
abstract class MultiValueExpression extends ColumnExpression implements Expression {

	protected $colname;
	protected $values;
	
	/**
	 * Create a new instance with column name and values.
	 * @param string $colname The column name.
	 * @param array $values The values.
	 * @see ColumnExpression::__construct()
	 */
	public function __construct($colname, $values)
	{
		parent::__construct($colname);
		$this->values = $values;
	}
	
	/**
	 * Gets the operator for this expression (IN or NOT IN).
	 * 
	 * This method must be defined by the subclasses to return their operator.
	 * 
	 * @return string The operator for the expression.
	 */
	abstract protected function getOperator();
	
	/**
	 * @see Expression::buildSql()
	 */
	public function buildSql(&$params)
	{
		$col = $this->createQueryColumn();

		if ($this->values !== null) {

			if (empty($this->values)) {
			    // a SQL error will result if we have COLUMN IN (), so replace it with an expression
			    // that will always evaluate to FALSE for Criteria::IN and TRUE for Criteria::NOT_IN
				$sql .= $this->getEmptyValuesSql();
			} else {

				$sql = $col->getQualifiedSql() . ' ' . $this->getOperator();

				foreach($this->values as $value) {
                    $params[] = new ColumnValue($col->getColumnMap(), $value);
                }

                $inString = '(' . substr(str_repeat("?,", count($this->values)), 0, -1) . ')';
                $sql = $inString;
			}

		} else {

			// value is null, which means it was either not specified or specifically
            // set to null.

			$sql = $this->getNullValueSql($col);
		}
		
		return $sql;
	}

	/**
     * An empty array will raise a SQL error, so this method returns a SQL expression that evaluates to TRUE or FALSE to use in its place.
     * @return string
     */
	abstract protected function getEmptyValuesSql();

	/**
	 * Gets the SQL to use if the passed-in value is NULL.
	 */
	abstract protected function getNullValueSql(QueryColumn $col);
	
}


/**
 * The abstract superclass for AND and OR logical expressions.
 */
abstract class LogicExpression extends BaseExpressionContainer {

	private $expressions = array();

	/**
	 * Create a new LogicExpression with variable number of arguments.
	 */
	public function __construct()
	{
		$args = func_get_args();
		foreach($args as $arg) {
			$this->add($arg);
		}
	}

	/**
	 * @see Expression::buildSql()
	 */
	public function buildSql(&$params)
	{
        // each expression gets nested in ()
        $sql = '(';
        $and = 0;
		foreach($this->expressions as $expr) {
			if ($and++) { $sql .= ' ' . $this->getOperator() . ' '; }
			$sql .= $expr->buildSql($params);
		}
		$sql .= ')';
		return $sql;
	}
	
	/**
	 * @see ExpressionContainer::add()
	 */
	public function add(Expression $expr)
	{
		if ($expr->getIgnoreCase() === null) {
			$expr->setIgnoreCase($this->getIgnoreCase());
		}
		$this->expressions[] = $expr;
	}

	/**
	 * Return the Iterator (IteratorAggregate interface).
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->expressions);
	}
	
	/**
	 * Returns the logical operator (AND, OR) for this expression container.
	 * This is implemented by each subclass.
	 * @return string Logical operator.
	 */ 
	abstract public function getOperator();
	
}

// -----------------------------------------------------------------------------
// C O N C R E T E   C L A S S E S
// -----------------------------------------------------------------------------


/**
 * A "column = value" expression.
 */
class EqualExpr extends ColumnValueExpression {

	/**
	 * @see ColumnValueExpression::getOperator()
	 */
	protected function getOperator()
	{
		return "=";
	}

	protected function getNullValueSql()
	{
		return $this->getQueryColumn()->getQualifiedSql() . " IS NULL";
	}

}

/**
 * A "column != value" expression.
 */
class NotEqualExpr extends ColumnValueExpression {
	
	/**
	 * @see ColumnValueExpression::getOperator()
	 */
	protected function getOperator()
	{
		return "!=";
	}

	protected function getNullValueSql()
	{
		return $this->getQueryColumn()->getQualifiedSql() . " IS NOT NULL";
	}

}

/**
 * A "column > value" expression.
 */
class GreaterExpr extends ColumnValueExpression {
	
	/**
	 * @see ColumnValueExpression::getOperator()
	 */
	protected function getOperator()
	{
		return ">";
	}
}

/**
 * A "column >= value" expression.
 */
class GreaterEqualExpr extends ColumnValueExpression {
	
	/**
	 * @see ColumnValueExpression::getOperator()
	 */
	protected function getOperator()
	{
		return ">=";
	}
}

/**
 * A "column < value" expression.
 */
class LessExpr extends ColumnValueExpression {
	
	/**
	 * @see ColumnValueExpression::getOperator()
	 */
	protected function getOperator()
	{
		return "<";
	}
}

/**
 * A "column <= value" expression.
 */
class LessEqualExpr extends ColumnValueExpression {
	
	/**
	 * @see ColumnValueExpression::getOperator()
	 */
	protected function getOperator()
	{
		return "<=";
	}
}

/**
 * A "column LIKE value" expression.
 */
class LikeExpr extends ColumnValueExpression {
	
	/**
	 * @see ColumnValueExpression::getIgnoreCaseSql()
	 */
	protected function getIgnoreCaseSql(QueryColumn $col, DBAdapter $db)
	{
		$op = $this->getOperator();
		if ($db instanceof DBPostgres) {
			$op = "ILIKE";
			return $col->getQualifiedSql() . " " . $op . " ?";
		} else {
			return $db->ignoreCase($col->getQualifiedSql()) . ' ' . $op . ' ' . $db->ignoreCase("?");
		}
	}
	
	/**
	 * @see ColumnValueExpression::getOperator()
	 */
	protected function getOperator()
	{
		return "LIKE";
	}
}

/**
 * A "column NOT LIKE value" expression.
 */
class NotLikeExpr extends ColumnValueExpression {
	
	/**
	 * @see ColumnValueExpression::getIgnoreCaseSql()
	 */
	protected function getIgnoreCaseSql(QueryColumn $col, DBAdapter $db)
	{
		$op = $this->getOperator();
		if ($db instanceof DBPostgres) {
			$op = "NOT ILIKE";
			return $col->getQualifiedSql() . " " . $op . " ?";
		} else {
			return $db->ignoreCase($col->getQualifiedSql()) . ' ' . $op . ' ' . $db->ignoreCase("?");
		}
	}
	
	/**
	 * @see ColumnValueExpression::getOperator()
	 */
	protected function getOperator()
	{
		return "NOT LIKE";
	}
}

/**
 * A "column IN (...)" expression.
 */
class InExpr extends MultiValueExpression {
	
	/**
	 * @see MultiValueExpression::getEmptyValuesSql()
	 */
	protected function getEmptyValuesSql()
	{
		return "1<>1";
	}
	
	/**
	 * @see MultiValueExpression::getOperator()
	 */
	protected function getOperator()
	{
		return "IN";
	}
	
	/**
	 * Builds SQL if the value is NULL.
	 */
	protected function getNullValueSql(QueryColumn $col)
	{
		return $col->getQualifiedSql() . ' IS NULL';
	}
}

/**
 * A "column NOT IN (...)" expression.
 */
class NotInExpr extends MultiValueExpression {
	
	/**
	 * @see MultiValueExpression::getEmptyValuesSql()
	 */
	protected function getEmptyValuesSql()
	{
		return "1=1";
	}
	
	/**
	 * @see MultiValueExpression::getOperator()
	 */
	protected function getOperator()
	{
		return "NOT IN";
	}
	
	/**
	 * Gets the SQL to use if the passed-in value is NULL.
	 */
	protected function getNullValueSql(QueryColumn $col)
	{
		return $col->getQualifiedSql() . ' IS NOT NULL';
	}
}


/**
 * A expression container that relates expressions by "AND" logical operator.
 */
class AndExpr extends LogicExpression {

	/**
	 * @see LogicExpression::getOperator()
	 */
	public function getOperator()
	{
		return "AND";
	}

}

/**
 * A expression container that relates expressions by "OR" logical operator.
 */
class OrExpr extends LogicExpression {
	
	/**
	 * @see LogicExpression::getOperator()
	 */
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
	
	/**
	 * @see Expression::buildSql()
	 */
	public function buildSql(&$params)
	{
		return $this->sql;
	}
	
	/**
	 * Returns the custom SQL in this class.
	 * @return string The SQL
	 */
	public function getSql()
	{
		return $this->sql;
	}
	
}
 

