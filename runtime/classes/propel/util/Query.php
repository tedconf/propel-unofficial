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

include_once 'propel/util/Criteria.php';

/**
 * This is a utility class for holding criteria information for a query.
 *
 * BasePeer constructs SQL statements based on the values in this class.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 */
class Query  {
	
	const ALL = "ALL";
	const DISTINCT = "DISTINCT";
	
	private $criteria;
	private $useTransaction = false;
	private $selectModifiers = array();
	private $selectColumns = array();
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
    public function __construct(Criteria $c)
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
	
	public function getQueryTable()
	{
		return $this->criteria->getQueryTable();
	}
	
	public function getDbName()
	{
		return $this->criteria->getDbName();
	}
	
    /**
     * Will force the sql represented by this query to be executed within a transaction.
	 * 
	 * This is here primarily to support the oid type in
     * postgresql.  Though it can be used to require any single sql statement
     * to use a transaction.
     * 
     * @return Query This modified Query object.
     */
    public function setUseTransaction($v)
    {
        $this->useTransaction = (boolean) $v;
        return $this;
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
	 * Gets the joins for this query.
	 * @return array Join[]
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
	 * @return Query This modified Query object.
	 */
	public function addJoin(QueryColumn $leftCol, QueryColumn $rightCol, $joinType = Join::IMPLICIT)
	{
		$this->joins[] = new Join($leftCol, $rightCol, $joinType);
		return $this;
	}
	
	/**
	 * Adds (all) columns for the specified table to the SELECT query.
	 * @param QueryColumn $qc
	 * @return Query This modified Query object.
	 */
	public function addSelectColumn(QueryColumn $qc)
	{
		$this->selectColumnsTables[] = $qc;
		return $this;
	}
	
	/**
	 * Adds the select columns for a specified QueryTable to this Query.
	 * @param QueryTable $qt
	 * @return Query This modified Query object.
	 */
	public function addSelectColumnsForTable(QueryTable $qt)
	{
		$tMap = $qt->getTableMap();
		foreach($tMap->getColumns() as $colMap) {
			$this->selectColumns[] = new ActualQueryColumn($colMap, $qt);
		}
		return $this;
	}
	
	/**
	 * Adds the select columns for the current table to this Query.
	 * @return Query This modified Query object.
	 */
	public function addDefaultSelectColumns()
	{
		$this->addSelectColumnsForTable($this->getQueryTable());
		return $this;
	}
	
	/**
	 * Gets select columns.
	 * @return array QueryColumn[]
	 */
	public function getSelectColumns()
	{
		return $this->selectColumns;
	}
	
	/**
	 * Adds a select modifier to this query.
	 * @param string $modifier
	 * @return Query This modified Query object.
	 */
	public function addSelectModifier($modifier)
	{
		$this->selectModifiers[] = $modifier;
		return $this;
	}
	
	/**
	 * Get the select modifiers for this query.
	 * @rturn array string[]
	 */
	public function getSelectModifiers()
	{
		return $this->selectMOidifers;
	}
	
    /**
     * Set max number of rows to return.
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
     * @return int The number of rows to return.
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Get offset.
     * @return An int with the value for offset.
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Get order-by columns.
     * @return array OrderByColumn[]
     */
    public function getOrderByColumns()
    {
        return $this->orderByColumns;
    }
	
	/**
	 * Adds a group-by column to this Query.
	 * @param QueryColumn $qc
	 */
	public function addGroupByColumn(QueryColumn $qc)
	{
		$htis->groupByColumns[] = $qc;
	}
	
    /**
     * Get group by columns.
     * @return array QueryColumn[]
     */
    public function getGroupByColumns()
    {
        return $this->groupByColumns;
    }

	/**
     * Add order by column name, explicitly specifying ascending.
     *
     * @param name The name of the column to order by.
     * @return Query This modified Query object.
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
     * @return Query This modified Query object.
     */
    public function addDescOrderBy($name)
    {
    	$qt = $this->criteria->getQueryTable();
    	$this->orderByColumns[] = $qt->createOrderByColumn($name, OrderByColumn::DESC);
        return $this;
    }
    
    /**
     * Adds an already-configured OrderByColumn to this Query.
	 * @param OrderByColumn $orderBy 
     */
    public function addOrderBy(OrderByColumn $orderBy)
	{
    	$this->orderByColumns[] = $orderBy;
    	return $this;
    }

    /**
     * Get "having" expression.
     *
     * @return Expression An Expression object that is the having clause.
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
     * @return Query This modified Query object.
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
