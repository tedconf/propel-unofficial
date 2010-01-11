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

/**
 * This class extends the Criteria by adding runtime introspection abilities
 * in order to ease the building of queries.
 * 
 * A ModelCriteria requires additional information to be initialized. 
 * Using a model name and tablemaps, a ModelCriteria can do more powerful things than a simple Criteria
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    propel.runtime.query
 */
class ModelCriteria extends Criteria
{
	const MODEL_CLAUSE = "MODEL CLAUSE";
	const MODEL_CLAUSE_ARRAY = "MODEL CLAUSE ARRAY";
	const MODEL_CLAUSE_LIKE = "MODEL CLAUSE LIKE";
	const MODEL_CLAUSE_SEVERAL = "MODEL CLAUSE SEVERAL";

	const FORMAT_STATEMENT = 'PropelStatementFormatter';
	const FORMAT_ARRAY = 'PropelArrayFormatter';
	const FORMAT_OBJECT = 'PropelObjectFormatter';
	const FORMAT_ON_DEMAND = 'PropelOnDemandFormatter';
	
	protected $modelName;
	protected $modelPeerName;
	protected $modelAlias;
	protected $useAliasInSQL = false;
	protected $tableMap;
	protected $primaryCriteria;
	protected $formatter;
	protected $defaultFormatterClass = ModelCriteria::FORMAT_OBJECT;
	protected $with = array();
		
	/**
	 * Creates a new instance with the default capacity which corresponds to
	 * the specified database.
	 *
	 * @param     string $dbName The dabase name
	 * @param     string $modelName The phpName of a model, e.g. 'Book'
	 * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
	 */
	public function __construct($dbName = null, $modelName, $modelAlias = null)
	{
		$this->setDbName($dbName);
		$this->originalDbName = $dbName;
		$this->modelName = $modelName;
		$this->modelPeerName = constant($this->modelName . '::PEER');
		$this->modelAlias = $modelAlias;
		$this->tableMap = Propel::getDatabaseMap($this->getDbName())->getTableByPhpName($this->modelName);
	}
	
	/**
	 * Returns the name of the class for this model criteria
	 *
	 * @return    string
	 */
	public function getModelName()
	{
		return $this->modelName;
	}
	
	/**
	 * Sets the alias for the model in this query
	 *
	 * @param    string $modelAlias The model alias
	 * @param    boolean $useAliasInSQL Whether to use the alias in the SQL code (false by default)
	 *
	 * @return ModelCriteria The current object, for fluid interface
	 */
	public function setModelAlias($modelAlias, $useAliasInSQL = false)
	{
		if ($useAliasInSQL) {
			$this->addAlias($modelAlias, $this->tableMap->getName());
			$this->useAliasInSQL = true;
		}
		$this->modelAlias = $modelAlias;
		
		return $this;
	}
	
	/**
	 * Returns the alias of the main class for this model criteria
	 *
	 * @return    string The model alias
	 */
	public function getModelAlias()
	{
		return $this->modelAlias;
	}
	
	/**
	 * Return the string to use in a clause as a model prefix for the main model
	 *
	 * @return    string The model alias if it exists, the model name if not
	 */
	public function getModelAliasOrName()
	{
		return $this->modelAlias ? $this->modelAlias : $this->modelName;
	}
	
	/**
	 * Returns the name of the Peer class for this model criteria
	 *
	 * @return string
	 */
	public function getModelPeerName()
	{
		return $this->modelPeerName;
	}
	
	/**
	 * Returns the TabkleMap object for this Criteria
	 *
	 * @return TableMap
	 */
	public function getTableMap()
	{
		return $this->tableMap;
	}
	
	/**
	 * Sets the formatter to use for the find() output
	 * Formatters must extend PropelFormatter
	 * Use the ModelCriteria constants for class names:
	 * <code>
	 * $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
	 * </code>
	 *
	 * @param     mixed $formatter a formatter class name, or a formatter instance
	 * @return    ModelCriteria The current object, for fluid interface
	 */
	public function setFormatter($formatter)
	{
		if(is_string($formatter)) {
			$formatter = new $formatter();
		}
		if (!$formatter instanceof PropelFormatter) {
			throw new PropelException('setFormatter() only accepts classes extending PropelFormatter');
		}
		$formatter->setCriteria($this);
		$this->formatter = $formatter;
		
		return $this;
	}
  
	/**
	 * Gets the formatter to use for the find() output
	 * Defaults to an instance of ModelCriteria::$defaultFormatterClass, i.e. PropelObjectsFormatter 
	 *
	 * @return PropelFormatter
	 */
	public function getFormatter()
	{
		if (null === $this->formatter) {
			$this->setFormatter($this->defaultFormatterClass);
		}
		return $this->formatter;
	}
	
	/**
	 * Adds a condition on a column based on a pseudo SQL clause
	 * but keeps it for later use with combine()
	 * Until combine() is called, the condition is not added to the query
	 * Uses introspection to translate the column phpName into a fully qualified name
	 * <code>
	 * $c->condition('cond1', 'b.Title = ?', 'foo');
	 * </code>
	 *
	 * @see        Criteria::add()
	 * 
	 * @param      string $conditionName A name to store the condition for a later combination with combine()
	 * @param      string $clause The pseudo SQL clause, e.g. 'AuthorId = ?'
	 * @param      mixed  $value A value for the condition
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function condition($conditionName, $clause, $value = null)
	{
		$this->addCond($conditionName, $this->getCriterionForClause($clause, $value), null, null);
		
		return $this;
	}
  
	/**
	 * Adds a condition on a column based on a column phpName and a value
	 * Uses introspection to translate the column phpName into a fully qualified name
	 * Warning: recognizes only the phpNames of the main Model (not joined tables)
	 * <code>
	 * $c->filterBy('Title', 'foo');
	 * </code>
	 *
	 * @see        Criteria::add()
	 * 
	 * @param      string $column     A string representing thecolumn phpName, e.g. 'AuthorId'
	 * @param      mixed  $value      A value for the condition
	 * @param      string $comparison What to use for the column comparison, defaults to Criteria::EQUAL
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function filterBy($column, $value, $comparison = Criteria::EQUAL)
	{
		return $this->add($this->getRealColumnName($column), $value, $comparison);
	}
	
	/**
	 * Adds a list of conditions on the columns of the current model
	 * Uses introspection to translate the column phpName into a fully qualified name
	 * Warning: recognizes only the phpNames of the main Model (not joined tables)
	 * <code>
	 * $c->filterByArray(array(
	 *  'Title'     => 'War And Peace',
	 *  'Publisher' => $publisher
	 * ));
	 * </code>
	 *
	 * @see        filterBy()
	 * 
	 * @param      mixed $conditions An array of conditions, using column phpNames as key
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function filterByArray($conditions)
	{
		foreach ($conditions as $column => $args) {
			call_user_func_array(array($this, 'filterBy' . $column), (array) $args);
		}
		
		return $this;
	}
	
	/**
	 * Adds a condition on a column based on a pseudo SQL clause
	 * Uses introspection to translate the column phpName into a fully qualified name
	 * <code>
	 * // simple clause
	 * $c->where('b.Title = ?', 'foo');
	 * // named conditions
	 * $c->condition('cond1', 'b.Title = ?', 'foo');
	 * $c->condition('cond2', 'b.ISBN = ?', 12345);
	 * $c->where(array('cond1', 'cond2'), Criteria::LOGICAL_OR);
	 * </code>
	 *
	 * @see Criteria::add()
	 * 
	 * @param      mixed $clause A string representing the pseudo SQL clause, e.g. 'Book.AuthorId = ?'
	 *                           Or an array of condition names
	 * @param      mixed  $value A value for the condition
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function where($clause, $value = null)
	{
		if (is_array($clause)) {
			// where(array('cond1', 'cond2'), Criteria::LOGICAL_OR)
			$criterion = $this->getCriterionForConditions($clause, $value);	
		} else {
			// where('Book.AuthorId = ?', 12)
			$criterion = $this->getCriterionForClause($clause, $value);
		}
		$this->add($criterion, null, null);
		
		return $this;
	}
	
	/**
	 * Adds a condition on a column based on a pseudo SQL clause
	 * Uses introspection to translate the column phpName into a fully qualified name
	 * <code>
	 * // simple clause
	 * $c->orWhere('b.Title = ?', 'foo');
	 * // named conditions
	 * $c->condition('cond1', 'b.Title = ?', 'foo');
	 * $c->condition('cond2', 'b.ISBN = ?', 12345);
	 * $c->orWhere(array('cond1', 'cond2'), Criteria::LOGICAL_OR);
	 * </code>
	 *
	 * @see Criteria::addOr()
	 * 
	 * @param      string $clause The pseudo SQL clause, e.g. 'AuthorId = ?'
	 * @param      mixed  $value A value for the condition
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function orWhere($clause, $value = null)
	{
		if (is_array($clause)) {
			// orWhere(array('cond1', 'cond2'), Criteria::LOGICAL_OR)
			$criterion = $this->getCriterionForConditions($clause, $value);
		} else {
			// orWhere('Book.AuthorId = ?', 12)
			$criterion = $this->getCriterionForClause($clause, $value);
		}
		$this->addOr($criterion, null, null);
		
		return $this;
	}

	/**
	 * Adds a having condition on a column based on a pseudo SQL clause
	 * Uses introspection to translate the column phpName into a fully qualified name
	 * <code>
	 * // simple clause
	 * $c->having('b.Title = ?', 'foo');
	 * // named conditions
	 * $c->condition('cond1', 'b.Title = ?', 'foo');
	 * $c->condition('cond2', 'b.ISBN = ?', 12345);
	 * $c->having(array('cond1', 'cond2'), Criteria::LOGICAL_OR);
	 * </code>
	 *
	 * @see Criteria::addHaving()
	 * 
	 * @param      mixed $clause A string representing the pseudo SQL clause, e.g. 'Book.AuthorId = ?'
	 *                           Or an array of condition names
	 * @param      mixed  $value A value for the condition
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function having($clause, $value = null)
	{
		if (is_array($clause)) {
			// having(array('cond1', 'cond2'), Criteria::LOGICAL_OR)
			$criterion = $this->getCriterionForConditions($clause, $value);
		} else {
			// having('Book.AuthorId = ?', 12)
			$criterion = $this->getCriterionForClause($clause, $value);
		}
		$this->addHaving($criterion);
		
		return $this;
	}
		
	/**
	 * Adds an ORDER BY clause to the query
	 * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
	 * Infers $column and $order from $columnName and some optional arguments
	 * Examples:
	 *   $c->orderBy('Book.CreatedAt')
	 *    => $c->addAscendingOrderByColumn(BookPeer::CREATED_AT)
	 *   $c->orderBy('Book.CategoryId', 'desc')
	 *    => $c->addDescendingOrderByColumn(BookPeer::CATEGORY_ID)
	 *
	 * @param string $columnName The column to order by
	 * @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function orderBy($columnName, $order = Criteria::ASC)
	{
		list($column, $realColumnName) = $this->getColumnFromName($columnName);
		if (!$column instanceof ColumnMap) {
			throw new PropelException('ModelCriteria::orderBy() expects a valid column name (e.g. Book.Title) as first argument');
		}
		$order = strtoupper($order);
		
		switch ($order) {
			case Criteria::ASC:
				$this->addAscendingOrderByColumn($realColumnName);
				break;
			case Criteria::DESC:
				$this->addDescendingOrderByColumn($realColumnName);
				break;
			default:
				throw new PropelException('ModelCriteria::orderBy() only accepts "asc" or "desc" as argument');
		}
		
		return $this;
	}
	
	/**
	 * Adds a GROUB BY clause to the query
	 * Usability layer on top of Criteria::addGroupByColumn()
	 * Infers $column $columnName
	 * Examples:
	 *   $c->groupBy('Book.AuthorId')
	 *    => $c->addGroupByColumn(BookPeer::AUTHOR_ID)
	 *
	 * @param      string $columnName The column to group by
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function groupBy($columnName)
	{
		list($column, $realColumnName) = $this->getColumnFromName($columnName);
		if (!$column instanceof ColumnMap) {
			throw new PropelException('ModelCriteria::groupBy() expects a valid column name (e.g. Book.AuthorId) as first argument');
		}
		$this->addGroupByColumn($realColumnName);
		
		return $this;
	}
  
	/**
	 * Adds a DISTINCT clause to the query
	 * Alias for Criteria::setDistinct()
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function distinct()
	{
		$this->setDistinct();
		
		return $this;
	}
	
	/**
	 * Adds a LIMIT clause (or its subselect equivalent) to the query
	 * Alias for Criteria:::setLimit()
	 *
	 * @param      int $limit Maximum number of results to return by the query
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function limit($limit)
	{
		$this->setLimit($limit);
		
		return $this;
	}
	
	/**
	 * Adds an OFFSET clause (or its subselect equivalent) to the query
	 * Alias for of Criteria::setOffset()
	 *
	 * @param      int $offset Offset of the first result to return
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function offset($offset)
	{
		$this->setOffset($offset);
		
		return $this;
	}

	/**
	 * Adds a JOIN clause to the query
	 * Infers the ON clause from a relation name
	 * Uses the Propel table maps, based on the schema, to guess the related columns
	 * Beware that the default JOIN operator is INNER JOIN, while Criteria defaults to WHERE
	 * Examples:
	 * <code>
	 *   $c->join('Book.Author');
	 *    => $c->addJoin(BookPeer::AUTHOR_ID, AuthorPeer::ID, Criteria::INNER_JOIN);
	 *   $c->join('Book.Author', Criteria::RIGHT_JOIN);
	 *    => $c->addJoin(BookPeer::AUTHOR_ID, AuthorPeer::ID, Criteria::RIGHT_JOIN);
	 *   $c->join('Book.Author a', Criteria::RIGHT_JOIN);
	 *    => $c->addAlias('a', AuthorPeer::TABLE_NAME);
	 *    => $c->addJoin(BookPeer::AUTHOR_ID, 'a.ID', Criteria::RIGHT_JOIN);
	 * </code>
	 * 
	 * @param      string $relation Relation to use for the join
	 * @param      string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function join($relation, $joinType = Criteria::INNER_JOIN)
	{
		// relation looks like '$leftName.$relationName $relationAlias'
		list($fullName, $relationAlias) = self::getClassAndAlias($relation);
		list($leftName, $relationName) = explode('.', $fullName);
		
		// find the TableMap for the left table using the $leftName
		if ($leftName == $this->getModelAliasOrName()) {
			$previousJoin = null;
			$tableMap = $this->getTableMap();
		} elseif (array_key_exists($leftName, $this->joins)) {
			$previousJoin = $this->joins[$leftName];
			$tableMap = $previousJoin->getTableMap();
		} else {
			throw new PropelException('Unknown table or alias ' . $leftName);
		}
		
		// find the RelationMap in the TableMap using the $relationName
		if(!$tableMap->hasRelation($relationName)) {
			throw new PropelException('Unknown relation ' . $relationName . ' on the ' . $leftName .' table');
		}
		$relationMap = $tableMap->getRelation($relationName);
		
		// create a ModelJoin object for this join
		$rightTable = $relationMap->getRightTable();
		$join = new ModelJoin();
		$join->setJoinType($joinType);
		$join->setTableMap($rightTable);
		if(null !== $previousJoin) {
			$join->setPreviousJoin($previousJoin);
		}
		$leftTableAlias = array_key_exists($leftName, $this->aliases) ? $leftName : null;
		$join->setRelationMap($relationMap, $leftTableAlias, $relationAlias);
		
		// add the ModelJoin to the current object
		if($relationAlias !== null) {
			$this->addAlias($relationAlias, $rightTable->getName());
			$this->joins[$relationAlias] = $join;
		} else {
			$this->joins[$relationName] = $join;
		}
		
		return $this;
	}
	
	/**
	 * Adds a JOIN clause to the query and hydrates the related objects
	 * Shortcut for $c->join()->with()
	 * <code>
	 *   $c->joinWith('Book.Author');
	 *    => $c->join('Book.Author');
	 *    => $c->with('Author');
	 *   $c->joinWith('Book.Author a', Criteria::RIGHT_JOIN);
	 *    => $c->join('Book.Author a', Criteria::RIGHT_JOIN);
	 *    => $c->with('a');
	 * </code>
	 * 
	 * @param      string $relation Relation to use for the join
	 * @param      string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function joinWith($relation, $joinType = Criteria::INNER_JOIN)
	{
		$this->join($relation, $joinType);
		end($this->joins);
		$relationName = key($this->joins);
		$this->with($relationName);
		
		return $this;
	}
	
	/**
	 * Adds a relation to hydrate together with the main object
	 * The relation must be initialized via a join() prior to calling with()
	 * Examples:
	 * <code>
	 *   $c->join('Book.Author');
	 *   $c->with('Author');
	 *
	 *   $c->join('Book.Author a', Criteria::RIGHT_JOIN);
	 *   $c->with('a');
	 * </code>
	 * 
	 * @param      string $relation Relation to use for the join
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function with($relation)
	{
		if (!array_key_exists($relation, $this->joins)) {
			throw new PropelException('Unknown relation name or alias ' . $relation);
		}
		$join = $this->joins[$relation];
		if ($join->getRelationMap()->getType() == RelationMap::ONE_TO_MANY) {
			throw new PropelException('with() only allows hydration for many-to-one or one-to-one relationships');
		}
		// check that the columns of the main class are already added
		if (!$this->hasSelectClause()) {
			$this->addSelfSelectColumns();
		}
		// add the columns of the related class
		$this->addRelationSelectColumns($relation);
		
		// list the join for later hydration in the formatter
		$this->with[]= $join;
		
		return $this;
	}

	/**
	 * Gets the array of joins to hydrate together with the main object
	 * 
	 * @see       with()
	 * @return    array
	 */
	public function getWith()
	{
		return $this->with;
	}

	/**
	 * Initializes a secondary ModelCriteria object, to be later merged with the current object
	 *
	 * @see       ModelCriteria::endUse()
	 * @param     string $relationName Relation name or alias
	 * @param     string $secondCriteriaClass Classname for the ModelCriteria to be used
	 *
	 * @return    ModelCriteria The secondary criteria object
	 */
	public function useQuery($relationName, $secondaryCriteriaClass = null)
	{
		if (!array_key_exists($relationName, $this->joins)) {
			throw new PropelException('Unknown class or alias ' . $name);
		}
		$className = $this->joins[$relationName]->getTableMap()->getPhpName();
		if (null === $secondaryCriteriaClass) {
			$secondaryCriteria = PropelQuery::from($className);
		} else {
			$secondaryCriteria = new $secondaryCriteriaClass();
		}
		if ($className != $relationName) {
			$secondaryCriteria->setModelAlias($relationName, true);
		}
		$secondaryCriteria->setPrimaryCriteria($this);
		
		return $secondaryCriteria;
	}
	
	/**
	 * Finalizes a secondary criteria and merges it with its primary Criteria
	 *
	 * @see       Criteria::mergeWith()
	 *
	 * @return    ModelCriteria The primary criteria object
	 */
	public function endUse()
	{
		if (array_key_exists($this->modelAlias, $this->aliases)) {
			unset($this->aliases[$this->modelAlias]);
		}
		$primaryCriteria = $this->getPrimaryCriteria();
		$primaryCriteria->mergeWith($this);
		
		return $primaryCriteria;
	}
	
	/**
	 * Add the content of a Criteria to the current Criteria
	 * In case of conflict, the current Criteria keeps its properties
	 * @see Criteria::mergeWith()
	 * 
	 * @param     Criteria $criteria The criteria to read properties from
	 */
	public function mergeWith(Criteria $criteria)
	{
		parent::mergeWith($criteria);
		
		// merge with
		$this->with = array_merge($this->getWith(), $criteria->getWith());
		
		return $this;
	}
	
	/**
	 * Sets the primary Criteria for this secondary Criteria
	 *
	 * @param     ModelCriteria $criteria The primary criteria
	 */
	public function setPrimaryCriteria(ModelCriteria $criteria)
	{
		$this->primaryCriteria = $criteria;
	}

	/**
	 * Gets the primary criteria for this secondary Criteria
	 *
	 * @return     ModelCriteria The primary criteria
	 */
	public function getPrimaryCriteria()
	{
		return $this->primaryCriteria;
	}

	/**
	 * Adds the select columns for a the current table
	 *
	 * @return    ModelCriteria The current object, for fluid interface
	 */	
	public function addSelfSelectColumns()
	{
		call_user_func(array($this->modelPeerName, 'addSelectColumns'), $this, $this->useAliasInSQL ? $this->modelAlias : null);
		
		return $this;
	}
	
	/**
	 * Adds the select columns for a relation
	 *
	 * @param     string $relation The relation name or alias, as defined in join()
	 *
	 * @return    ModelCriteria The current object, for fluid interface
	 */
	public function addRelationSelectColumns($relation)
	{
		$join = $this->joins[$relation];
		call_user_func(array($join->getTableMap()->getPeerClassname(), 'addSelectColumns'), $this, $join->getRelationAlias());
		
		return $this;
	}
	
	/**
	 * Returns the class and alias of a string representing a model or a relation
	 * e.g. 'Book b' => array('Book', 'b')
	 * e.g. 'Book'   => array('Book', null)
	 *
	 * @param      string $class The classname to explode
	 *
	 * @return     array  list($className, $aliasName)
	 */
	public static function getClassAndAlias($class)
	{
	  if(strpos($class, ' ') !== false) {
	    list($class, $alias) = explode(' ', $class);
	  } else {
	    $alias = null;
	  }
	  return array($class, $alias);
	}
	
	/**
	 * Code to execute before every SELECT statement
	 * 
	 * @param     PropelPDO $con The connection object used by the query
	 */
	protected function basePreSelect(PropelPDO $con)
	{
		return $this->preSelect($con);
	}
	
	protected function preSelect(PropelPDO $con)
	{
	}

	/**
	 * Issue a SELECT query based on the current ModelCriteria
	 * and format the list of results with the current formatter
	 * By default, returns an array of model objects
	 * 
	 * @param     PropelPDO $con an optional connection object
	 *
	 * @return     mixed the list of results, formatted by the current formatter
	 */
	public function find($con = null)
	{
		$stmt = $this->getSelectStatement($con);
		
		return $this->getFormatter()->format($stmt);
	}

	/**
	 * Issue a SELECT ... LIMIT 1 query based on the current ModelCriteria
	 * and format the result with the current formatter
	 * By default, returns a model object
	 * 
	 * @param     PropelPDO $con an optional connection object
	 *
	 * @return    mixed the result, formatted by the current formatter
	 */
	public function findOne($con = null)
	{
		$this->limit(1);
		$stmt = $this->getSelectStatement($con);
		
		return $this->getFormatter()->formatOne($stmt);
	}
	
	/**
	 * Find object by primary key
	 * Behaves differently if the model has simple or composite primary key
	 * <code>
	 * // simple primary key
	 * $book  = $c->findPk(12, $con);
	 * // composite primary key
	 * $bookOpinion = $c->findPk(array(34, 634), $con);
	 * </code>
	 * @param     mixed $key Primary key to use for the query
	 * @param     PropelPDO $con an optional connection object
	 *
	 * @return    mixed the result, formatted by the current formatter
	 */
	public function findPk($key, $con = null)
	{
		$pkCols = $this->getTableMap()->getPrimaryKeyColumns();
		if (count($pkCols) == 1) {
			// simple primary key
			$pkCol = $pkCols[0];
			$this->add($pkCol->getFullyQualifiedName(), $key);
			return $this->findOne($con);
		} else {
			// composite primary key
			foreach ($pkCols as $pkCol) {
				$keyPart = array_shift($key);
				$this->add($pkCol->getFullyQualifiedName(), $keyPart);
			}
			return $this->findOne($con);
		}
	}

	/**
	 * Find objects by primary key
	 * Behaves differently if the model has simple or composite primary key
	 * <code>
	 * // simple primary key
	 * $books = $c->findPks(array(12, 56, 832), $con);
	 * // composite primary key
	 * $bookOpinion = $c->findPks(array(array(34, 634), array(45, 518), array(34, 765)), $con);
	 * </code>
	 * @param     array $keys Primary keys to use for the query
	 * @param     PropelPDO $con an optional connection object
	 *
	 * @return    mixed the list of results, formatted by the current formatter
	 */
	public function findPks($keys, $con = null)
	{
		$pkCols = $this->getTableMap()->getPrimaryKeyColumns();
		if (count($pkCols) == 1) {
			// simple primary key
			$pkCol = array_shift($pkCols);
			$this->add($pkCol->getFullyQualifiedName(), $keys, Criteria::IN);
		} else {
			// composite primary key
			throw new PropelException('Multiple object retrieval is not implemented for composite primary keys');
		}
		return $this->find($con);
	}
	
	protected function getSelectStatement($con = null)
	{
	  if ($con === null) {
			$con = Propel::getConnection($this->getDbName(), Propel::CONNECTION_READ);
		}
		
		// we may modify criteria, so copy it first
		$criteria = clone $this;

		if (!$criteria->hasSelectClause()) {
			$criteria->addSelfSelectColumns();
		}
		
		$con->beginTransaction();
		try {
			$criteria->basePreSelect($con);
			$stmt = BasePeer::doSelect($criteria, $con);
			$con->commit();
		} catch (PropelException $e) {
			$con->rollback();
			throw $e;
		}
		
		return $stmt;
	}

	/**
	 * Apply a condition on a column and issues the SELECT query
	 *
	 * @see       filterBy()
	 * @see       find()
	 *
	 * @param     string    $column A string representing the column phpName, e.g. 'AuthorId'
	 * @param     mixed     $value  A value for the condition
	 * @param     PropelPDO $con    An optional connection object
	 *
	 * @return    mixed the list of results, formatted by the current formatter
	 */
	public function findBy($column, $value, $con = null)
	{
		$this->filterBy($column, $value);

		return $this->find($con);
	}

	/**
	 * Apply a list of conditions on columns and issues the SELECT query
	 * <code>
	 * $c->findByArray(array(
	 *  'Title'     => 'War And Peace',
	 *  'Publisher' => $publisher
	 * ), $con);
	 * </code>
	 *
	 * @see       filterByArray()
	 * @see       find()
	 * 
	 * @param     mixed $conditions An array of conditions, using column phpNames as key
	 * @param     PropelPDO $con an optional connection object
	 *
	 * @return    mixed the list of results, formatted by the current formatter
	 */
	public function findByArray($conditions, $con = null)
	{
		$this->filterByArray($conditions);

		return $this->find($con);
	}
	
	/**
	 * Apply a condition on a column and issues the SELECT ... LIMIT 1 query
	 *
	 * @see       filterBy()
	 * @see       findOne()
	 *
	 * @param     mixed $column A string representing thecolumn phpName, e.g. 'AuthorId'
	 * @param     mixed  $value A value for the condition
	 * @param     PropelPDO $con an optional connection object
	 *
	 * @return    mixed the result, formatted by the current formatter
	 */
	public function findOneBy($column, $value, $con = null)
	{
		$this->filterBy($column, $value);

		return $this->findOne($con);
	}

	/**
	 * Apply a list of conditions on columns and issues the SELECT ... LIMIT 1 query
	 * <code>
	 * $c->findOneByArray(array(
	 *  'Title'     => 'War And Peace',
	 *  'Publisher' => $publisher
	 * ), $con);
	 * </code>
	 *
	 * @see       filterByArray()
	 * @see       findOne()
	 * 
	 * @param     mixed $conditions An array of conditions, using column phpNames as key
	 * @param     PropelPDO $con an optional connection object
	 *
	 * @return    mixed the list of results, formatted by the current formatter
	 */
	public function findOneByArray($conditions, $con = null)
	{
		$this->filterByArray($conditions);

		return $this->findOne($con);
	}
	
	/**
	 * Issue a SELECT COUNT(*) query based on the current ModelCriteria
	 * 
	 * @param PropelPDO $con an optional connection object
	 *
	 * @return integer the number of results
	 */
	public function count($con = null)
	{
		if ($con === null) {
			$con = Propel::getConnection($this->getDbName(), Propel::CONNECTION_READ);
		}
		
		$criteria = clone $this;
		$criteria->setDbName($this->getDbName()); // Set the correct dbName
		$criteria->clearOrderByColumns(); // ORDER BY won't ever affect the count

		// We need to set the primary table name, since in the case that there are no WHERE columns
		// it will be impossible for the BasePeer::createSelectSql() method to determine which
		// tables go into the FROM clause.
		$criteria->setPrimaryTableName(constant($this->modelPeerName.'::TABLE_NAME'));

		if (!$criteria->hasSelectClause()) {
			call_user_func(array($this->modelPeerName, 'addSelectColumns'), $criteria);
		}

		$con->beginTransaction();
		try {
			$criteria->basePreSelect($con);
			$stmt = BasePeer::doCount($criteria, $con);
			$con->commit();
		} catch (PropelException $e) {
			$con->rollback();
			throw $e;
		}		

		if ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$count = (int) $row[0];
		} else {
			$count = 0; // no rows returned; we infer that means 0 matches.
		}
		$stmt->closeCursor();
		
		return $count;
	}
	
	/**
	 * Issue a SELECT query based on the current ModelCriteria
	 * and uses a page and a maximum number of results per page
	 * to compute an offet and a limit.
	 * 
	 * @param     int $page number of the page to start the pager on. Page 1 means no offset
	 * @param     int $maxPerPage maximum number of results per page. Determines the limit
	 * @param     PropelPDO $con an optional connection object
	 *
	 * @return    PropelModelPager a pager object, supporting iteration
	 */
	public function paginate($page = 1, $maxPerPage = 10, $con = null)
	{
		$pager = new PropelModelPager($this, $maxPerPage);
		$pager->setPage($page);
		$pager->init();
		
		return $pager;
	}

	/**
	 * Code to execute before every DELETE statement
	 * 
	 * @param     PropelPDO $con The connection object used by the query
	 */
	protected function basePreDelete(PropelPDO $con)
	{
		return $this->preDelete($con);
	}
	
	protected function preDelete(PropelPDO $con)
	{
	}
	
	/**
	 * Issue a DELETE query based on the current ModelCriteria
	 * An optional hook on basePreDelete() can prevent the actual deletion
	 * 
	 * @param PropelPDO $con an optional connection object
	 *
	 * @return integer the number of deleted rows
	 */
	public function delete($con = null)
	{
		if (count($this->getMap()) == 0) {
			throw new PropelException('delete() expects a Criteria with at least one condition. Use deleteAll() to delete all the rows of a table');
		}
		
		if ($con === null) {
			$con = Propel::getConnection($this->getDbName(), Propel::CONNECTION_READ);
		}
		
		$criteria = clone $this;
		$criteria->setDbName($this->getDbName());

		$con->beginTransaction();
		try {
			if(!$affectedRows = $criteria->basePreDelete($con)) {
				$affectedRows = $criteria->doDelete($con);
			}
			$con->commit();
		} catch (PropelException $e) {
			$con->rollback();
			throw $e;
		}
		
		return $affectedRows;
	}

	/**
	 * Issue a DELETE query based on the current ModelCriteria
	 * This method is called by ModelCriteria::delete() inside a transaction
	 * 
	 * @param PropelPDO $con a connection object
	 *
	 * @return integer the number of deleted rows
	 */
	public function doDelete($con)
	{
		$affectedRows = BasePeer::doDelete($this, $con);
		call_user_func(array($this->modelPeerName, 'clearInstancePool'));
		call_user_func(array($this->modelPeerName, 'clearRelatedInstancePool'));
		
		return $affectedRows;
	}
	
	/**
	 * Issue a DELETE query based on the current ModelCriteria deleting all rows in the table
	 * An optional hook on basePreDelete() can prevent the actual deletion
	 * 
	 * @param PropelPDO $con an optional connection object
	 *
	 * @return integer the number of deleted rows
	 */
	public function deleteAll($con = null)
	{
		if ($con === null) {
			$con = Propel::getConnection($this->getDbName(), Propel::CONNECTION_WRITE);
		}
		$con->beginTransaction();
		try {
			if(!$affectedRows = $this->basePreDelete($con)) {
				$affectedRows = $this->doDeleteAll($con);
			}
			$con->commit();
			return $affectedRows;
		} catch (PropelException $e) {
			$con->rollBack();
			throw $e;
		}
		
		return $affectedRows;
	}
	
	/**
	 * Issue a DELETE query based on the current ModelCriteria deleting all rows in the table
	 * This method is called by ModelCriteria::deleteAll() inside a transaction
	 * 
	 * @param PropelPDO $con a connection object
	 *
	 * @return integer the number of deleted rows
	 */
	public function doDeleteAll($con)
	{
		$affectedRows = BasePeer::doDeleteAll(constant($this->modelPeerName.'::TABLE_NAME'), $con);
		call_user_func(array($this->modelPeerName, 'clearInstancePool'));
		call_user_func(array($this->modelPeerName, 'clearRelatedInstancePool'));
		
		return $affectedRows;
	}
	
	/**
	 * Code to execute before every UPDATE statement
	 * 
	 * @param     array $values The associatiove array of columns and values for the update
	 * @param     PropelPDO $con The connection object used by the query
	 */
	protected function basePreUpdate(&$values, PropelPDO $con)
	{
		return $this->preUpdate($values, $con);
	}

	protected function preUpdate(&$values, PropelPDO $con)
	{
	}
	
	/**
	* Issue an UPDATE query based the current ModelCriteria and a list of changes.
	* An optional hook on basePreUpdate() can prevent the actual update.
	* Beware that behaviors based on hooks in the object's save() method
	* will only be triggered if you force individual saves, i.e. if you pass true as second argument.
	*
	* @param      array $values Associative array of keys and values to replace
	* @param      PropelPDO $con an optional connection object
	* @param      boolean $forceIndividualSaves If false (default), the resulting call is a BasePeer::doUpdate(), ortherwise it is a series of save() calls on all the found objects
	*
	* @return     Integer Number of updated rows
	*/
	public function update($values, $con = null, $forceIndividualSaves = false)
	{
		if (!is_array($values)) {
			throw new PropelException('set() expects an array as first argument');
		}
		if (count($this->getJoins())) {
			throw new PropelException('set() does not support multitable updates, please do not use join()');
		}
		
		if ($con === null) {
			$con = Propel::getConnection($this->getDbName(), Propel::CONNECTION_WRITE);
		}
		
		$criteria = clone $this;
		$criteria->setPrimaryTableName(constant($this->modelPeerName.'::TABLE_NAME'));
		
		$con->beginTransaction();
		try {
			
			if(!$affectedRows = $criteria->basePreUpdate($values, $con, $forceIndividualSaves)) {
				$affectedRows = $criteria->doUpdate($values, $con, $forceIndividualSaves);
			}
			
			$con->commit();
		} catch (PropelException $e) {
			$con->rollBack();
			throw $e;
		}
		
		return $affectedRows;
	}
	
	/**
	* Issue an UPDATE query based the current ModelCriteria and a list of changes.
	* This method is called by ModelCriteria::update() inside a transaction.
	*
	* @param      array $values Associative array of keys and values to replace
	* @param      PropelPDO $con a connection object
	* @param      boolean $forceIndividualSaves If false (default), the resulting call is a BasePeer::doUpdate(), ortherwise it is a series of save() calls on all the found objects
	*
	* @return     Integer Number of updated rows
	*/
	public function doUpdate($values, $con, $forceIndividualSaves = false)
	{
		if($forceIndividualSaves) {
		
			// Update rows one by one
			$objects = $this->setFormatter(ModelCriteria::FORMAT_OBJECT)->find($con);
			foreach ($objects as $object) {
				foreach ($values as $key => $value) {
					$object->setByName($key, $value);
				}
			}
			$objects->save($con);
			$affectedRows = count($objects);
			
		} else {
			
			// update rows in a single query
			$set = new Criteria();
			foreach ($values as $columnName => $value) {
				$realColumnName = $this->getTableMap()->getColumnByPhpName($columnName)->getFullyQualifiedName();
				$set->add($realColumnName, $value);
			}
			$affectedRows = BasePeer::doUpdate($this, $set, $con);
			call_user_func(array($this->modelPeerName, 'clearInstancePool'));
			call_user_func(array($this->modelPeerName, 'clearRelatedInstancePool'));
		}
		
		return $affectedRows;
	}
	
	/**
	 * Creates a Criterion object based on a list of existing condition names and a comparator
	 *
	 * @param      array $conditions The list of condition names, e.g. array('cond1', 'cond2')
	 * @param      string  $comparator A comparator, Criteria::LOGICAL_AND (default) or Criteria::LOGICAL_OR
	 *
	 * @return     Criterion a Criterion or ModelCriterion object
	 */
	protected function getCriterionForConditions($conditions, $comparator = null)
	{
		$comparator = (null === $comparator) ? Criteria::LOGICAL_AND : $comparator;
		$this->combine($conditions, $comparator, 'propel_temp_name');
		$criterion = $this->namedCriterions['propel_temp_name'];
		unset($this->namedCriterions['propel_temp_name']);
		
		return $criterion;
	}
	  
	/**
	 * Creates a Criterion object based on a SQL clause and a value
	 * Uses introspection to translate the column phpName into a fully qualified name
	 *
	 * @param      string $clause The pseudo SQL clause, e.g. 'AuthorId = ?'
	 * @param      mixed  $value A value for the condition
	 *
	 * @return     Criterion a Criterion or ModelCriterion object
	 */
	protected function getCriterionForClause($clause, $value)
	{
		$clause = trim($clause);
		if($this->replaceNames($clause)) {
			// at least one column name was found and replaced in the clause
			// this is enough to determine the type to bind the parameter to
			if (preg_match('/IN \?$/i', $clause) !== 0) {
				$operator = ModelCriteria::MODEL_CLAUSE_ARRAY;
			} elseif (preg_match('/LIKE \?$/i', $clause) !== 0) {
				$operator = ModelCriteria::MODEL_CLAUSE_LIKE;
			} elseif (substr_count($clause, '?') > 1) {
				$operator = ModelCriteria::MODEL_CLAUSE_SEVERAL;
			} else {
				$operator = ModelCriteria::MODEL_CLAUSE;
			}
		  $criterion = new ModelCriterion($this, $this->replacedColumns[0], $value, $operator, $clause);
		  if ($this->currentAlias != '') {
		  	$criterion->setTable($this->currentAlias);
		  }
		} else {
			// no column match in clause, must be an expression like '1=1'
			if (strpos($clause, '?') !== false) {
				throw new PropelException("Cannot determine the column to bind to the parameter in clause '$clause'");
			}
		  $criterion = new Criterion($this, null, $clause, Criteria::CUSTOM);
		}
		return $criterion;		
	}
	
	/**
	 * Replaces complete column names (like Article.AuthorId) in an SQL clause
	 * by their exact Propel column fully qualified name (e.g. article.AUTHOR_ID)
	 * but ignores the column names inside quotes
	 *
	 * Note: if you know a way to do so in one step, and in an efficient way, I'm interested :)
	 *
	 * @param string $clause SQL clause to inspect (modified by the method)
	 *
	 * @return boolean Whether the method managed to find and replace at least one column name
	 */
	protected function replaceNames(&$clause)
	{
		$this->replacedColumns = array();
		$this->currentAlias = '';
		$this->foundMatch = false;
		$regexp = <<<EOT
|
	(["'][^"']*?["'])?  # string
	([^"']+)?           # not string
|x
EOT;
		$clause = preg_replace_callback($regexp, array($this, 'doReplaceName'), $clause);
		return $this->foundMatch;
	}
	
	/**
	 * Callback function to replace expressions containing column names with expressions using the real column names
	 * Handles strings properly
	 * e.g. 'CONCAT(Book.Title, "Book.Title") = ?'
	 *   => 'CONCAT(book.TITLE, "Book.Title") = ?'
	 *
	 * @param array $matches Matches found by preg_replace_callback
	 *
	 * @return string the expression replacement
	 */
	protected function doReplaceName($matches)
	{
		if(!$matches[0]) {
			return '';
		}
		// replace names only in expressions, not in strings delimited by quotes
		return $matches[1] . preg_replace_callback('/\w+\.\w+/', array($this, 'doReplaceNameInExpression'), $matches[2]);
	}
	
	/**
	 * Callback function to replace column names by their real name in a clause
	 * e.g.  'Book.Title IN ?'
	 *    => 'book.TITLE IN ?'
	 *
	 * @param array $matches Matches found by preg_replace_callback
	 *
	 * @return string the column name replacement
	 */
	protected function doReplaceNameInExpression($matches)
	{
		$key = $matches[0];
		list($column, $realColumnName) = $this->getColumnFromName($key);
		if ($column instanceof ColumnMap) {
			$this->replacedColumns[]= $column;
			$this->foundMatch = true;
			return $realColumnName;
		} else {
			return $key;
		}
	}

	/**
	 * Finds a column and a SQL translation for a pseudo SQL column name
	 * Respects table aliases previously registered in a join() or addAlias()
	 * Examples:
	 * <code>
	 * $c->getColumnFromName('Book.Title');
	 *   => array($bookTitleColumnMap, 'book.TITLE')
	 * $c->join('Book.Author a')
	 *   ->getColumnFromName('a.FirstName');
	 *   => array($authorFirstNameColumnMap, 'a.FIRST_NAME')
	 * </code>
	 *
	 * @param      string $phpName String representing the column name in a pseudo SQL clause, e.g. 'Book.Title'
	 *
	 * @return     array List($columnMap, $realColumnName)
	 */
	protected function getColumnFromName($phpName, $failSilently = true)
	{
		if (strpos($phpName, '.') === false) {
			$class = $this->getModelAliasOrName();
		} else {
			list($class, $phpName) = explode('.', $phpName);
		}
		
		
		if ($class == $this->getModelAliasOrName()) {
			// column of the Criteria's model
			$tableMap = $this->getTableMap();
		} elseif (array_key_exists($class, $this->joins)) {
			// column of a relations's model
			$tableMap = $this->joins[$class]->getTableMap();
		} else {
			if ($failSilently) {
				return array(null, null);
			} else {
				throw new PropelException('Unknown model or alias ' . $class);
			}
		}
		
		if ($tableMap->hasColumnByPhpName($phpName)) {
			$column = $tableMap->getColumnByPhpName($phpName);
			if (array_key_exists($class, $this->aliases)) {
				$this->currentAlias = $class;
				$realColumnName = $class . '.' . $column->getName();
			} else {
				$realColumnName = $column->getFullyQualifiedName();
			}
			return array($column, $realColumnName);
		} else {
			if ($failSilently) {
				return array(null, null);
			} else {
				throw new PropelException('Unknown column ' . $phpName . ' on model or alias ' . $class);
			}
		}
	}
	
	/**
	 * Return a fully qualified column name corresponding to a simple column phpName
	 * Uses model alias if it exists
	 * Warning: restricted to the columns of the main model
	 * e.g. => 'Title' => 'book.TITLE'
	 *
	 * @param string $columnName the Column phpName, without the table name
	 *
	 * @return string the fully qualified column name
	 */
	protected function getRealColumnName($columnName)
	{
		if (!$this->getTableMap()->hasColumnByPhpName($columnName)) {
			throw new PropelException('Unkown column ' . $columnName . ' in model ' . $this->modelName);
		}
		if ($this->useAliasInSQL) {
			return $this->modelAlias . '.' . $this->getTableMap()->getColumnByPhpName($columnName)->getName();
		} else {
			return $this->getTableMap()->getColumnByPhpName($columnName)->getFullyQualifiedName();
		}
	}

	/**
	 * Changes the table part of a a fully qualified column name if a true model alias exists
	 * e.g. => 'book.TITLE' => 'b.TITLE' 
	 * This is for use as first argument of Criteria::add()
	 *
	 * @param     string $colName the fully qualified column name, e.g 'book.TITLE' or BookPeer::TITLE
	 *
	 * @return    string the fully qualified column name, using table alias if applicatble
	 */
	protected function getAliasedColName($colName)
	{
		if ($this->useAliasInSQL) {
			return $this->modelAlias . substr($colName, strpos($colName, '.'));
		} else {
			return $colName;
		}
	}

	/**
	 * Overrides Criteria::add() to force the use of a true table alias if it exists
	 *
	 * @see        Criteria::add()
	 * @param      string $column The colName of column to run the comparison on (e.g. BookPeer::ID)
	 * @param      mixed $value
	 * @param      string $comparison A String.
	 *
	 * @return     ModelCriteria A modified Criteria object.
	 */	
	protected function addUsingAlias($p1, $value = null, $comparison = null)
	{
		return $this->add($this->getAliasedColName($p1), $value, $comparison);
	}

	/**
	 * Handle the magic
	 * Supports findByXXX(), findOneByXXX(), filterByXXX(), orderByXXX(), and groupByXXX() methods,
	 * where XXX is a column phpName.
	 * Supports XXXJoin(), where XXX is a join direction (in 'left', 'right', 'inner')
	 */
	public function __call($name, $arguments)
	{
		// Maybe it's a magic call to one of the methods supporting it, e.g. 'findByTitle'
		static $methods = array('findBy', 'findOneBy', 'filterBy', 'orderBy', 'groupBy');
		foreach ($methods as $method)
		{
			if(strpos($name, $method) === 0)
			{
				$columns = substr($name, strlen($method));
				if(in_array($method, array('findBy', 'findOneBy')) && strpos($columns, 'And') !== false) {
					$method = $method . 'Array';
					$columns = explode('And', $columns);
					$conditions = array();
					foreach ($columns as $column) {
						$conditions[$column] = array_shift($arguments);
					}
					array_unshift($arguments, $conditions);
				} else {
					array_unshift($arguments, $columns);
				}
				return call_user_func_array(array($this, $method), $arguments);
			}
		}
		
		// Maybe it's a magic call to a qualified join method, e.g. 'leftJoin'
		if(($pos = strpos($name, 'Join')) > 0)
		{
			$type = substr($name, 0, $pos);
			if(in_array($type, array('left', 'right', 'inner')))
			{
				$joinType = strtoupper($type) . ' JOIN';
				array_push($arguments, $joinType);
				$method = substr($name, $pos);
				// no lcfirst in php<5.3...
				$method = ($method == 'Join') ? 'join' : 'joinWith';
				return call_user_func_array(array($this, $method), $arguments);
			}
		}
   
		throw new PropelException(sprintf('Undefined method %s::%s()', __CLASS__, $name));
	}
}