<?php
/*
 *  $Id$
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
class Query implements StatementBuilder {

	const ALL = "ALL";
	const DISTINCT = "DISTINCT";

	private $criteria;
	private $useTransaction = false;
	private $selectModifiers = array();
	private $orderByColumns = array();
	private $groupByColumns = array();
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

	/**
	 * Get the QueryTable for this Query.
	 * This is equivalent to getting the QueryTable for the primary Criteria class in this Query.
	 * @return QueryTable
	 */
	public function getQueryTable()
	{
		return $this->criteria->getQueryTable();
	}

	/**
	 * Gets the database name key for this Query.
	 * This is equivalent to getting the database name key from the primary Criteria for this Query.
	 * @return string
	 */
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
	 * Adds a specific column to the SELECT query.
	 * @param mixed $col QueryColumn or string column name (assumed to be in primary Criteria's QueryTable).
	 * @return Query This modified Query object.
	 */
	public function addSelectColumn($col)
	{
		if (!$col instanceof QueryColumn) {
			$col = $this->getQueryTable()->createQueryColumn($col);
		}
		$this->selectColumns[] = $col;
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
	 *
	 */
	public function clearSelectColumns()
	{
		$this->selectColumns = array();
		return $this;
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
		return $this->selectModifiers;
	}

	/**
	 * Clears the select modifiers.
	 */
	public function clearSelectModifiers()
	{
		$this->selectModifiers = array();
		return $this;
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
	 * Set the offset (0-based) for which to start retrieving rows.
	 * @param int $offset
	 */
	public function setOffset($offset)
	{
		$this->offset = $offset;
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
	 * @param mixed $col QueryColumn or string column name (assumed to be in primary Criteria's QueryTable).
	 */
	public function addGroupByColumn($col)
	{
		if (!$col instanceof QueryColumn) {
			$col = $this->getQueryTable()->createQueryColumn($col);
		}
		$htis->groupByColumns[] = $col;
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
	 *
	 */
	public function clearOrderByColumns()
	{
		$this->orderByColumns = array();
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


	/**
	 * Builds the SQL for this Query.
	 * @see StatementBuilder#buildSql()
	 */
	public function buildSql(&$bindParams)
	{

		$this->criteria = $this->getCriteria();
		$dbname = $this->criteria->getDbName();

		// we don't need to use DATABASE_NAME constants anymore, but clearly
		// it involves a little less dereferencing ....
		// $dbMap = $this->criteria->getQueryTable()->getTableMap()->getDatabase();

		// FIXME - these methods should be re-thought, since there's a more efficient
		// way to get the map directly from Criteria
		$db = Propel::getAdapter($dbname);
		$dbMap = Propel::getDatabaseMap($dbname);

		$selectModifiers = array();
		$selectClause = array();
		$fromClause = array();
		$joinClause = array();
		$joinTables = array();
		$whereClause = array();
		$orderByClause = array();
		$groupByClause = array();


		// FIXME ... we should try to handle this on a Criteria-by-Criteria basis
		$ignoreCase = $this->criteria->getIgnoreCase();

		// -------------------------------------
		// (1) SELECT MODIFIERS
		// -------------------------------------
		$selectModifiers = $this->getSelectModifiers();

		// -------------------------------------
		// (2) SELECT COLUMNS
		// -------------------------------------
		$selectColumns = $this->getSelectColumns();

		foreach($selectColumns as $selCol) {
			if (!is_object($selCol)) print new DDException(count($selectColumns));
			$selectClause[] = $selCol->getQualifiedSql();
		}


		// -------------------------------------
		// (3) FROM TABLES
		// -------------------------------------

		$fromClause[] = $this->criteria->getQueryTable()->getFromClauseSql();

		// FIXME - we need to also add any tables that aren't represented by JOINS
		// For that, we want a $this->getUnjoinedTables() method.
		//
		// Specifically, we need to support the fact that some expression values may be
		// columns of other tables.


		// -------------------------------------
		// (1) WHERE CLAUSE
		// -------------------------------------

		// Add the criteria to WHERE clause, adding any params to passed-in array
		$whereFromCriteria = $this->criteria->buildSql($bindParams);
		if ($whereFromCriteria) {
			$whereClause[] = $whereFromCriteria;
		}

		// -------------------------------------
		// (4) JOINS (FROM CLAUSE, WHERE CLAUSE)
		// -------------------------------------


		// Loop through the joins,
		// joins with a null join type will be added to the FROM clause and the condition added to the WHERE clause.
		// joins of a specified type: the LEFT side will be added to the fromClause and the RIGHT to the joinClause
		// New Code.

		foreach ($this->getJoins() as $join) { // we'll only loop if there's actually something here

			// FIXME - most of this stuff could be moved into the Join class.  There's no
			// reason that I can see why it needs to be in BasePeer ...

			// The join might have been established using an alias name
			$leftCol = $join->getLeftColumn();
			$rightCol = $join->getRightColumn();

			$leftTable = $join->getLeftTable();
			$rightTable = $join->getRightTable();

			// build the condition
			// TODO - consider allowing more complex conditions here.  We get into some trouble when we actully
			// want to use an Expression interface, however, because Expressions are inherently single-table.
			if ($ignoreCase) {
				$condition = $leftCol->ignoreCase($leftCol->getQualifiedSql()) . '=' . $rightCol->ignoreCase($rightCol->getQualifiedSql());
			} else {
				$condition = $leftCol->getQualifiedSql() . '=' . $rightCol->getQualifiedSql();
			}

			// add 'em to the queues..
			if ( $join->getJoinType() !== Join::IMPLICIT ) {
				$joinTables[] = $rightTable->getFromClauseSql();
				$joinClause[] = $join->getJoinType() . ' ' . $rightTable->getFromClauseSql() . " ON (".$condition.")";
			} else {
				$fromClause[] = $leftTable->getFromClauseSql();
				$fromClause[] = $rightTable->getFromClauseSql();
				// we don't modify Criteria here, instead we just add this to our $whereClause string[] array
				$whereClause[] = $condition;
			}
		}

		// Unique from clause elements
		$fromClause = array_unique($fromClause);

		// tables should not exist in both the from and join clauses
		if ($joinTables && $fromClause) {
			foreach ($fromClause as $fi => $ftableAndAlias) {
				if (in_array($ftableAndAlias, $joinTables)) {
					unset($fromClause[$fi]);
				}
			}
		}

		// -------------------------------------
		// (5) GROUP BY COLUMNS
		// -------------------------------------

		// Add the GROUP BY columns
		$groupByColumns = $this->getGroupByColumns();
		foreach($groupByColumns as $groupByCol) {
			$groupByClause[] = $groupByCol->getQualifiedSql();
		}

		// -------------------------------------
		// (6) HAVING CLAUSE
		// -------------------------------------

		$having = $this->getHaving();
		$havingSql = null;
		if ($having !== null) {
			$havingSql = $having->buildSql($bindParams);
		}

		// -------------------------------------
		// (7) ORDER BY COLUMNS
		// -------------------------------------

		$orderByColumns = $this->getOrderByColumns();
		if (!empty($orderByColumns)) {
			foreach($orderByColumns as $orderByColumn) {
				$direction = $orderByColumn->getDirection();
				if ($ignoreCase && ($orderByColumn instanceof ActualOrderByColumn) && $orderByColumn->getColumnMap()->isText()) {
					$orderByClause[] = $db->ignoreCaseInOrderBy($orderByColumn->getQualifiedSql()) . ' ' . $direction;
				} else {
					$orderByClause[] = $orderByColumn->getQualifiedSql() . ' ' . $direction;
				}
			}
		}

		// -------------------------------------
		// (8) CREATING THE QUERY SQL
		// -------------------------------------

		// Build the SQL from the arrays we compiled
		$sql =  "SELECT "
				.($selectModifiers ? implode(" ", $selectModifiers) . " " : "")
				.implode(", ", $selectClause)
				." FROM ".implode(", ", $fromClause)
								.($joinClause ? ' ' . implode(' ', $joinClause) : '')
				.($whereClause ? " WHERE ".implode(" AND ", $whereClause) : "")
				.($groupByClause ? " GROUP BY ".implode(",", $groupByClause) : "")
				.($havingSql ? " HAVING ".$havingSql : "")
				.($orderByClause ? " ORDER BY ".implode(",", $orderByClause) : "");

		// APPLY OFFSET & LIMIT to the query.
		if ($this->getLimit() || $this->getOffset()) {
			$db->applyLimit($sql, $this->getOffset(), $this->getLimit());
		}

		return $sql;
	}

	/**
	 * Adds deep copy support.
	 * @todo -c In order for this to work, we need to implement __clone in all the related classes too.
	 */
	public function __cloneTODO()
	{
		$this->criteria = clone($this->criteria);
		foreach ($this->orderByColumns as $k => $v) {
			$this->orderByColumns[$k] = clone($v);
		}
		foreach ($this->groupByColumns as $k => $v) {
			$this->groupByColumns[$k] = clone($v);
		}
		foreach ($this->selectColumns as $k => $v) {
			$this->selectColumns[$k] = clone($v);
		}
		foreach ($this->joins as $k => $v) {
			$this->joins[$k] = clone($v);
		}
	}

}
