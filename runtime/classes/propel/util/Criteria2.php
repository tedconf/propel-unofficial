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
class Criteria2 extends BaseExpressionContainer implements ExpressionContainer {

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
			if ($expr instanceof LogicExpression) {
				$this->container = $expr;
			} else {
				$this->container = new AndExpr();
				$this->container->add($expr);
			}
		} else { // we already have a container
			$this->container->add($expr);
		}
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
		return $this->getQueryColumn()->getQualifiedName() . " IS NULL";
	}

}


class NotEqualExpr extends ColumnValueExpression {

	protected function getOperator()
	{
		return "!=";
	}

	protected function getNullValueSql()
	{
		return $this->getQueryColumn()->getQualifiedName() . " IS NOT NULL";
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
	 * @param ColumnMan $col
	 * @param mixed $value
	 */
	public function __construct(QueryColumn $col, $value)
	{
		$this->column = $col;
		$this->value = $value;
	}

	public function getQueryColumn()
	{
		return $this->column;
	}

	public function setQueryColumn(QueryColumn $col)
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
