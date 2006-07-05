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
 * The most basic interface for anything object that can build SQL statements (or snippets) 
 * which may or may not have some associated bind parameters. 
 */
interface StatementBuilder {

	/**
	 * Returns the SQL built by this StatementBuilder and adds any parameters to the passed-in
	 * ColumnValue[] array.
	 * @param &$bindParams ColumnValue[] The array of ColumnValue objects that any value parameters should be added to.
	 * @return string The built SQL 
	 */
	public function buildSql(&$bindParams);

}

/**
 * The Expression interface is the basic building block of expressions in Proel.
 */
interface Expression extends StatementBuilder {

	/**
	 * Set whether this expression is case-insensitive.
	 * @param boolean $bit
	 * @return Expression This modified Expression object.
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
	 * @return Expression This modified Expression object.
	 */
	public function setQueryTable(QueryTable $table);

	/**
	 * Gets the QueryTable for this expression.
	 * @return QueryTable
	 */
	public function getQueryTable();

}

/**
 * Interface that the values (right-side) of an expression must implement.
 * @see ExpressionValue
 */ 
interface ColumnExpressionValue extends StatementBuilder {

	/**
	 * Sets the ColumnMap that this ColumnValueExpression will need to buildSql().
	 * @param ColumnMap $columnMap The ColumnMap object that the value(s) from this object will be associated with. 
	 */
	public function setColumnMap(ColumnMap $columnMap);
	
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
	 * @return Expression This modified Expression object.
	 */
	public function setQueryTable(QueryTable $table)
	{
		$this->queryTable = $table;
		return $this;
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
     * @return Expression This modified Expression object.
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
	 * Sets the QueryTable for this expression and optionally all children (overriding any previous value they may have had).
	 * @param QueryTable $table
	 * @return ExpressionContainer This modified ExpressionContainer object.
	 */
	public function setQueryTable(QueryTable $table)
	{
		parent::setQueryTable($table);
		foreach($this as $expr) {
			if ($expr->getQueryTable() === null) {
				$expr->setQueryTable($table);
			}
		}
		return $this;
	}

	/**
     * Sets ignore case for this expression and optionally all children (overriding any previous value they may have had).
     *
     * @param boolean $b True if case should be ignored.
     * @return ExpressionContainer A modified ExpressionContainer object.
     */
    public function setIgnoreCase($b)
    {
    	$b = (boolean) $b;
        parent::setIgnoreCase($b);
        foreach($this as $expr) {
        	if ($expr->getIgnoreCase() === null) {
        		$expr->setIgnoreCase($b);
        	}
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
		return Propel::getAdapter($qt->getDbName());
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
	 * @param mixed $value 	Value can be a simple scalar (in which case it is wrapped in a BindValueWrapper 
	 * 						and bound to statement later using PDOStatement->bindValue()) or it can be an
	 * 						StatementBiulder object, in which case the result of StatementBuilder->buildSql() 
	 * 						is added as the value in the buildSql() method.
	 */
	public function __construct($colname, $value)
	{
		parent::__construct($colname);
		if ($value !== null && !$value instanceof StatementBuilder) {
			$value = new BindValueWrapper($value);
		}
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
			
			if ($this->value instanceof BindValueWrapper) {
				$this->value->setColumnMap($col->getColumnMap());
			} 
			
			// Check to see whether we should attempt to ignore case for this
			// expression.  This may cause SQL errors for some types of ExpressionValue 
			// objects (e.g. LiteralSql).  But it makes more sense to attempt to apply
			// case-insensitivity, rather than silently ignore it.
			if ($this->getIgnoreCase() && $col->getColumnMap()->isStringType()) {
				$sql = $this->getIgnoreCaseSql($col, $params);
		    } else {
	        	$sql = $col->getQualifiedSql() . ' ' . $this->getOperator() . ' ' . $this->value->buildSql($params);
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
	 * @param array  &$bindParams
	 */  
	protected function getIgnoreCaseSql(QueryColumn $col, &$bindParams)
	{
		$db = $this->getAdapter();
		return $db->ignoreCase($col->getQualifiedSql()) . ' ' . $this->getOperator() . ' ' . $db->ignoreCase($this->value->buildSql($bindParams));
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
	 * @param mixed $values An array of values, an object that implements Traversable, or a single value (will be wrapped in array).
	 * @see ColumnExpression::__construct()
	 */
	public function __construct($colname, $values)
	{
		parent::__construct($colname);
		if ($values !== null) {
			if (!$values instanceof StatementBuilder) {
				if (!is_array($values)) { // expected
					if ($values instanceof Traversable) {
						$arr = array();
						foreach($values as $value) { // discarding keys
							$arr[] = $value;
						}
						$values = $arr;
					} else { // it's a single value that we should just wrap in an array
						$values = array($values);
					}
				}
				$values = new BindValueWrapper($values);
			}
		}
		//var_export($values);
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
			
			if ($this->values instanceof BindValueWrapper) {
				$this->values->setColumnMap($col->getColumnMap());
			}
			
			$builtSql = $this->values->buildSql($params);
			
			if (empty($builtSql)) {
			    // a SQL error will result if we have COLUMN IN (), so replace it with an expression
			    // that will always evaluate to FALSE for Criteria::IN and TRUE for Criteria::NOT_IN
				$sql = $this->getEmptyValuesSql();
			} else {
				$sql = $col->getQualifiedSql() . ' ' . $this->getOperator() . ' (' . $builtSql . ')';
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
        // each expression gets nested in () if there is more than one expression contained
        $sql = '';
        $size = count($this->expressions);
        if ($size > 1) $sql = '(';
        $and = 0;
		foreach($this->expressions as $expr) {
			if ($and++) { $sql .= ' ' . $this->getOperator() . ' '; }
			$sql .= $expr->buildSql($params);
		}
		if ($size > 1) $sql .= ')';
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
 * This class represents the default case of a PHP value in an expression.
 * 
 * This class supports both single values and multiple values (e.g. IN expressions). This
 * class should not be instantiated by the user.
 * 
 * @access protected
 */
class BindValueWrapper implements ColumnExpressionValue {
	
	private $value;
	private $columnMap;
	
	/**
	 * Create a new SingleValueWrapper with the specified value.
	 * @param mixed $value The value that this class contains.
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}
	
	/**
	 * Sets the ColumnMap to use when crating the bind values for this expression.
	 * @param ColumnMap $cm
	 */
	public function setColumnMap(ColumnMap $cm)
	{
		$this->columnMap = $cm;
	}
	
	/**
	 * Gets the SQL string to insert into the query.
	 * @param array &$bindParams ColumnValue[]
	 * @return string
	 * @see ExpressionValue#getSql()
	 * @throws PropelException - if ColumnMap was not set on this BindValueWrapper
	 */
	public function buildSql(&$bindParams)
	{
		if ($this->columnMap === null) {
			throw new PropelException("Cannot bind values for " . get_class($this) . " because no ColumnMap has been set.");
		}
		if (is_array($this->value)) {
			foreach($this->value as $value) {
				$bindParams[] = new ColumnValue($this->columnMap, $value);
			}
			return substr(str_repeat("?,", count($this->value)), 0, -1);
		} else {
			$bindParams[] = new ColumnValue($this->columnMap, $this->value);
			return '?';
		}
	}
	
}

/**
 * A class to hold literal SQL, which can be used as a value or as a standalone expresion.
 * 
 * This can be used a simple container for custom SQL (e.g. passed as a value
 * to another expression) or it can be used as a standalone Expression (e.g.
 * added to a Criteria).
 * 
 * For example:
 * <code>
 * $c = MyPeer::createCriteria();
 * $c->add(new EqualExpr(MyPeer::COL_NAME, new LiteralSql("now()"));
 * </code>
 * or
 * <code>
 * $c = MyPeer::createCriteria();
 * $c->add(new LiteralSql("char_length(mycolumn) = 4"));
 * </code>
 * 
 * Note that using literal SQL expressions in Propel is discourage, but we also recognize
 * that it's not always possible to do everything you need to do without them.
 */
class LiteralSql extends BaseExpression implements Expression {

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


/**
 * A generic "column op value" expr.
 */
class OpExpr extends ColumnValueExpression {
	
	/**
	 * @var string The operator.
	 */
	private $op;
	
	/**
	 * Construct a new ColValExpr with column name, value, and operator.
	 * @param string $colname
	 * @param mixed $value
	 * @param string $op The operator (e.g. '=').
	 */
	public function __construct($colname, $value, $op)
	{
		parent::__construct($colname, $value);
		$this->op = $op;
	}
	
	/**
	 * @see ColumnValueExpression::getOperator()
	 */
	public function getOperator()
	{
		return $this->op;
	}
}

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

	protected function getNullValueSql(QueryColumn $col)
	{
		return $col->getQualifiedSql() . " IS NULL";
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

	protected function getNullValueSql(QueryColumn $col)
	{
		return $col->getQualifiedSql() . " IS NOT NULL";
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
	protected function getIgnoreCaseSql(QueryColumn $col, &$bindParams)
	{
		$db = $this->getAdapter();
		$op = $this->getOperator();
		if ($db instanceof DBPostgres) {
			// Postgres has a special case-insensitive opearator
			$op = "ILIKE";
			return $col->getQualifiedSql() . ' ' . $op . ' ' . $this->value->buildSql($bindParams);
		} elseif ($db instanceof DBMySQL) { 
			// some databases are not case-sensitive at all in LIKE
			return $col->getQualifiedSql() . " " . $op . ' ' . $this->value->buildSql($bindParams);
		} else {
			// the default tends to be something like "UPPER(table.col) LIKE UPPER(?)"
			return $db->ignoreCase($col->getQualifiedSql()) . ' ' . $op . ' ' . $db->ignoreCase($this->value->buildSql($bindParams));
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
	protected function getIgnoreCaseSql(QueryColumn $col, &$bindParams)
	{
		$db = $this->getAdapter();
		$op = $this->getOperator();
		if ($db instanceof DBPostgres) {
			// Postgres has a special case-insensitive opearator
			$op = "NOT ILIKE";
			return $col->getQualifiedSql() . ' ' . $op . ' ' . $this->value->buildSql($bindParams);
		} elseif ($db instanceof DBMySQL) { 
			// some databases are not case-sensitive at all in LIKE
			return $col->getQualifiedSql() . " " . $op . ' ' . $this->value->buildSql($bindParams);
		} else {
			// the default tends to be something like "UPPER(table.col) NOT LIKE UPPER(?)"
			return $db->ignoreCase($col->getQualifiedSql()) . ' ' . $op . ' ' . $db->ignoreCase($this->value->buildSql($bindParams));
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
 

