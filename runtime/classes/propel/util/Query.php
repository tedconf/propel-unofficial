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

require_once 'propel/util/Criteria2.php';

//This class will probably contain a single Criteria class which stores information
//about FROM-clause (joins) and WHERE-clause criteria.  This is because the Criteria is
//a table-centric class, whereas this Query class could represent multiple tables.  Joins
//represent relationships from one table to another, so it makes sense to root them in
//the Criteria class.
//
//Joins can be broken down like this:
//	FROM table1
//		INNER JOIN table2 ON (EXPR)
//			INNER JOIN table2 ON (EXPR)
//
//And the API will probably look something like:
//  $c->addJoinedTable(TableMap, new EqualColExpr(COL1, COL2));
//
//It is also important to have a primary table and then follow the chain along to get
//the columns.  This is because the columns need to be added to the select list in
//a particular order -- and that is the job of this class.
//
//So, this class will be responsible for "rolling up" the nested table & column information
//from the Criteria class and presenting it in a friendly way for the BasePeer::createSelectSql()
//method.

/**
 * This is a utility class for holding criteria information for a query.
 *
 * BasePeer constructs SQL statements based on the values in this class.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 */
class Query  {

	private $criteria;
	private $useTransaction = false;
	private $selectColumnsTables = array();
	private $having;
	private $joins = array();
	private $limit;
	private $offset;
	
    /**
     * Creates a new Query instance with a primary Criteria.
	 *
	 * The passed-in Criteria will be the primary Criteria, and is used to determine
	 * this Query's primary table, from wich tables are joined, etc.
     *
     * @param Crieria The primary Criteria for this query.
     */
    public function __construct(Criteria2 $c)
    {
        $this->setCriteria($c);
    }
	
	/**
	 * Get the primary Criteria used in this Query.
	 * @return Criteria
	 */
	public function getCriteria()
	{
		return $this->criteria;
	}
	
	/**
	 * Set the primary Criteria for this Query.
	 *
	 * The primary Criteria determines the table from which other tables are joined, etc.
	 *
	 * @param Criteria The primary Criteria for this Query.
	 */
	public function setCriteria($c)
	{
		$this->criteria = $c;
	}
	
	public function getDbName()
	{
		return $this->criteria->getDbName();
	}
	
    /**
     * Will force the sql represented by this query to be executed within a transaction.
	 * This is here primarily to support the oid type in
     * postgresql.  Though it can be used to require any single sql statement
     * to use a transaction.
     * @return void
     */
    public function setUseTransaction($v)
    {
        $this->useTransaction = (boolean) $v;
    }

    /**
     * called by BasePeer to determine whether the sql command specified by
     * this criteria must be wrapped in a transaction.
     *
     * @return a <code>boolean</code> value
     */
    public function isUseTransaction()
    {
        return $this->useTransaction;
    }

	
	/**
	 *
	 * 
	 */
	public function getJoins()
	{
		return $this->joins;
	}

	/**
	 * Adds a JOIN to this Query.
	 * @param QueryColumn $leftCol The column for the left part of the join.
	 * @param QueryColumn $rightCol The column for the right part of the join.
	 * @param string $joinType The type of the JOIN (e.g. Join::IMPLICIT, Join::LEFT, Join::INNER)
	 */
	public function addJoin(QueryColumn $leftCol, QueryColumn $rightCol, $joinType = Join::IMPLICIT)
	{
		$this->joins[] = new QueryJoin($leftCol, $rightCol, $joinType);
	}
	
	/**
	 * Adds (all) columns for the specified table to the SELECT query.
	 * @param QueryTable $qt The table we want to add columns for.
	 */
	public function addSelectColumnsForTable(QueryTable $qt)
	{
		$this->selectColumnsTables[] = $qt;
	}
	
	/**
	 * Gets select columns by iterating over all the tables that were added with addSelectColumns(QueryTable)
	 * and returning an array of qualified column names.
	 */
	public function getSelectColumns()
	{
		$selectColumns = array();
		foreach($this->selectColumnsTables as $qt) {
			$alias = $qt->getAliasOrName();
			foreach($qt->getTableMap()->getColumns() as $colmap) {
				$selectColumns[] = $alias . '.' . $colmap->getName();
			}
		}
		return $selectColumns;
	}

    /**
     * Set max number of rows to return.
     *
     * @param int $limit The number of rows to return.
     * @return Query The modified Query object.
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }


    /**
     * Set max number of rows to return.
     *
     * @return The number of rows to return.
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Get offset.
     *
     * @return An int with the value for offset.
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Get order by columns.
     *
     * @return array An array with the name of the order columns.
     */
    public function getOrderByColumns()
    {
        return $this->orderByColumns;
    }

    /**
     * Get group by columns.
     *
     * @return array
     */
    public function getGroupByColumns()
    {
        return $this->groupByColumns;
    }

	/**
     * Add order by column name, explicitly specifying ascending.
     *
     * @param name The name of the column to order by.
     * @return A modified Criteria object.
     */
    public function addAscOrderBy($name)
    {
        $qt = $this->criteria->getQueryTable();
        $this->orderByColumns[] = $qt->createOrderByColumn($name, OrderByColumn::ASC);
        return $this;
    }

    /**
     * Add order by column name, explicitly specifying descending.
     *
     * @param string $name The name of the column to order by.
     * @return Criteria The modified Criteria object.
     */
    public function addDescOrderBy($name)
    {
    	$qt = $this->criteria->getQueryTable();
    	$this->orderByColumns[] = $qt->createOrderByColumn($name, OrderByColumn::DESC);
        return $this;
    }

    /**
     * Get Having Criterion.
     *
     * @return Criterion A Criterion object that is the having clause.
     */
    public function getHaving()
    {
        return $this->having;
    }

    /**
     * This method adds an Expression object to the Criteria as a having clause.
     *
     * <p>
     * <code>
     * $crit = BookPeer::createCriteria();
     * $crit->addHaving(new LessExpr(BookPeer::ID, 5);
     * </code>
     *
     * @param Expression $having
     * @return Criteria A modified Criteria object.
     */
    public function setHaving(Expression $having)
    {
        $this->having = $having;
        return $this;
    }
	
	/**
	 * Recurse through attached expressions and get all the distinct QueryTable for the Criteria.
	 * This includes tables that may not have been explicitly added through a JOIN.
	 * @return array TableMap[]
	 */
	public function getTablesFromCriteria()
	{
		$tables = array();
		$this->recurseFindTables($this, $tables);
		return $tables;
	}
	
	/**
	 * Recursive function that actually finds all table names associated with passed-in Expression object.
	 * Tables are added to the passed-in-by-ref $tables array.
	 * @param Expression $expr
	 * @param array &$tables
	 */
	private function recurseFindTables(Expression $expr, &$tables)
	{
		// check the table for current expression
		$key = $expr->getQueryTable()->getAliasOrName();
		if (!isset($tables[$key])) {
			$tables[$key] = $expr->getQueryTable();
		}
		// if it contains other expressions, then iterate over them & recurse
		if ($expr instanceof ExpressionContainer) {
			foreach($expr as $exprchild) {
				$this->recurseFindTables($exprchild, $tables);
			}
		}
	}

}

/**
 * The QueryTable class represents a table and its alias as it is being used in the query.
 *
 */
class QueryTable {

	private $tableMap;
	private $alias;
	private $columns = array();

	public function __construct(TableMap $tableMap, $alias = null)
	{
		$this->tableMap = $tableMap;
		$this->alias = $alias;
	}

	public function setTableMap(TableMap $table)
	{
		$this->tableMap = $table;
	}

	public function getTableMap()
	{
		return $this->tableMap;
	}

	public function setAlias($alias)
	{
		$this->alias = $alias;
	}

	public function getAlias()
	{
		return $this->alias;
	}

	public function getName()
	{
		return $this->tableMap->getName();
	}

	/**
	 * Convenience method to get the column alias or name, if no alias is defined.
	 * @return string
	 */
	public function getAliasOrName()
	{
		return $this->getAlias() ? $this->getAlias() : $this->getName();
	}
	
	/**
	 * Convenience method to get the SQL used in a FROM clause (e.g. "table alias").
	 * @return string
	 */ 
	public function getFromClauseSql()
	{
		return $this->getName() .  ' ' . $this->getAliasOrName();
	}
	
	/**
	 * Creates a QueryColumn from this table.
	 * 
	 * The column is initialized from the ColumnMap (looked up in TableMap) if it is not already set. 
	 * 
	 * @param string $colname
	 * @return QueryColumn
	 */
	public function createQueryColumn($colname)
	{
		$col = $this->tableMap->getColumn($colname);
		if (!$col) {
			throw new PropelException("Cannot load ".$colname." column from " . $this->getName() . " table.");
		}
		return new QueryColumn($col, $this);
	}

	/**
	 * Creates an OrderByColumn from this table.
	 * 
	 * The column is initialized from the ColumnMap (looked up in TableMap) if it is not already set. 
	 * 
	 * @param string $colname
	 * @param string $order The order for the sort (OrderByColumn::ASC or OrderByColumn::DESC).
	 * @return QueryColumn
	 */
	public function createOrderByColumn($colname, $order)
	{
		$col = $this->tableMap->getColumn($colname);
		if (!$col) {
			throw new PropelException("Cannot load ".$colname." column from " . $this->getName() . " table.");
		}
		return new OrderByColumn($col, $this, $order);
	}

}


/**
 * 
 * 
 */
class QueryColumn {

	private $queryTable;
	private $columnMap;

	public function __construct(ColumnMap $columnMap, QueryTable $queryTable)
	{
		$this->columnMap = $columnMap;
		$this->queryTable = $queryTable;
	}
	
	public function getQueryTable()
	{
		return $this->queryTable;
	}
	
	public function getName()
	{
		return $this->columnMap->getName();
	}

	public function getColumnMap()
	{
		return $this->columnMap;
	}

	public function setColumnMap(ColumnMap $columnMap)
	{
		$this->columnMap = $columnMap;
	}

	public function getQualifiedName()
	{
		return $this->queryTable->getAliasOrName() . '.' . $this->getName();
	}

 	/**
     * Performs DB-specific ignore case, but only if the column type necessitates it.
     * @param string $str The expression we want to apply the ignore case formatting to (e.g. the column name).
     * @param DBAdapter $db
     */
    public function ignoreCase($str)
    {
		if ($this->columnMap->isText()) {
			$db = $this->columnMap->getTable()->getDatabase()->getAdapter();
			return $db->ignoreCase($str);
		} else {
			return $str; 
		}
	}
}

/**
 * 
 * 
 */
class OrderByColumn extends QueryColumn {

	const ASC = 'ASC';
	const DESC = 'DESC';

	private $direction;

	public function __construct(ColumnMap $column, QueryTable $table, $direction = self::ASC)
	{
		parent::__construct($column, $table);
		$this->direction = $direction;
	}
	
	public function setDirection($direction)
	{
		$this->direction = $direction;
	}

	public function getDirection()
	{
		return $this->direction;
	}

}

/**
* Data object to describe a join between two tables, for example
* <pre>
* table_a LEFT JOIN table_b ON table_a.id = table_b.a_id
* </pre>
*/
class QueryJoin {

	const IMPLICIT = "IMPLICIT";
	const LEFT = "LEFT";
	const RIGHT = "RIGHT";
	const INNER = "INNER";

    /** the left column of the join condition */
    private $leftColumn = null;

    /** the right column of the join condition */
    private $rightColumn = null;

    /** the type of the join (LEFT JOIN, ...), or null */
    private $joinType = null;

    /**
     * Constructor
     *
     * @param Criteria $foreignCriteria The foreign Criteria, which is used to hold the table and any alias info.
     * @param QueryColumn $leftColumn the left column of the join condition;
     *        might contain an alias name
     * @param QueryColumn $rightColumn the right column of the join condition
     *        might contain an alias name
     * @param string $joinType the type of the join. Valid join types are
     *        null (adding the join condition to the where clause),
     *        Join::LEFT, Criteria::RIGHT, and Criteria::INNER
     *
     */
    public function __construct(QueryColumn $leftCol, QueryColumn $rightCol, $joinType = self::IMPLICIT)
    {
	    $this->leftColumn = $leftColumn;
	    $this->rightColumn = $rightColumn;
	    $this->joinType = $joinType;
    }

    /**
     * @return the type of the join, i.e. Criteria::LEFT_JOIN(), ...,
     *         or null for adding the join condition to the where Clause
     */
    public function getJoinType()
    {
	    return $this->joinType;
    }

    /**
     * @return the left column of the join condition
     */
    public function getLeftColumn()
    {
	    return $this->leftColumn;
    }

    /**
     *
     * @return QueryTable
     */
    public function getLeftTable()
	{
    	return $this->leftColumn->getQueryTable();
    }

    /**
     * @return the right column of the join condition
     */
    public function getRightColumn()
    {
	    return $this->rightColumn;
    }

    /**
     *
     * @return QueryTable
     */
    public function getRightTable()
	{
    	return $this->rightColumn->getQueryTable();
    }

    /**
     * returns a String representation of the class,
     * mainly for debugging purposes
     * @return a String representation of the class
     */
    public function toString()
    {
        $result = "";
        if ($this->joinType != null)
        {
            $result .= $this->joinType . " : ";
        }
        $result .= $this->leftColumn . "=" . $this->rightColumn . " (ignoreCase not considered)";

        return $result;
    }
}
