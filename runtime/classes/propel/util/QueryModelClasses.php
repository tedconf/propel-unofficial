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
 * This file contains a collection of classes and interfaces that are used to map database objects for queries. 
 */

/**
 * The QueryTable class represents a table and its alias as it is being used in the query.
 * 
 * The QueryTable contains a TableMap class; however, unlike TableMap, an instance of this class is created
 * every time a table needs to be referenced in a query.  The same table could be referenced in the query
 * using several different aliases, for example; each of those would be a separate instance of this class.  
 */
class QueryTable {
	
	/**
	 * @var string Cached copy of the database key. 
	 */
	private $dbname;
	
	private $tableMap;
	private $alias;
	private $columns = array();

	/**
	 * Construct a new QueryTable with a TableMap and (optional) alias.
	 * @param TableMap $tableMap
	 * @param string $alias
	 */
	public function __construct(TableMap $tableMap, $alias = null)
	{
		$this->tableMap = $tableMap;
		$this->alias = $alias;
	}
	
	/**
	 * Get the TableMap for this QueryTable
	 * @return TableMap
	 */
	public function getTableMap()
	{
		return $this->tableMap;
	}
	
	/**
	 * Get the database name key associated with this QueryTable.
	 * This value is cached for (significant??) performance gain.
	 * @return string
	 */
	public function getDbName()
	{
		if ($this->dbname === null) {
			$this->dbname = $this->tableMap->getDatabase()->getName();
		}
		return $this->dbname;
	}
	
	/**
	 * Sets the alias for this table.
	 * @param string $alias
	 */
	public function setAlias($alias)
	{
		$this->alias = $alias;
	}
	
	/**
	 * Gets the alias for this table.
	 * @return string
	 */
	public function getAlias()
	{
		return $this->alias;
	}
	
	/**
	 * Gets the actual name for this table.
	 * @return string 
	 */
	public function getName()
	{
		return $this->tableMap->getName();
	}

	/**
	 * Convenience method to get the table alias -- or name, if no alias is defined.
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
	 * Creates an ActualQueryColumn from this table.
	 * 
	 * The column is initialized from the ColumnMap (looked up in TableMap).  Unlike
	 * TableMap, it doesn't make sense to load all the columns and have a singleton
	 * pattern using getColumn(), because it is completely possible for the same
	 * actual column to be used multiple times with different aliases, etc. 
	 * 
	 * @param string $colname
	 * @return ActualColumn
	 * @throws PropelException - if column cannot be loaded from TableMap
	 */
	public function createQueryColumn($colname)
	{
		return new ActualQueryColumn($this->tableMap->getColumn($colname), $this);
	}

	/**
	 * Creates an ActualOrderByColumn from this table.
	 * 
	 * The column is initialized from the ColumnMap (looked up in TableMap).
	 * 
	 * @param string $colname
	 * @param string $order The order for the sort (OrderByColumn::ASC or OrderByColumn::DESC).
	 * @return ActualOrderByColumn
	 * @throws PropelException - if column cannot be loaded from TableMap
	 */
	public function createOrderByColumn($colname, $order)
	{
		return new ActualOrderByColumn($this->tableMap->getColumn($colname), $this, $order);
	}
	
	/**
	 * Creates a CustomQueryColumn from this table.
	 * 
	 * 
	 * @param string $sql
	 * @return QueryColumn
	 */
	public function createCustomQueryColumn($sql)
	{
		return new CustomQueryColumn($sql, $this);
	}

	/**
	 * Creates a CustomOrderByColumn from this table.
	 *  
	 * @param string $sql
	 * @param string $order The order for the sort (OrderByColumn::ASC or OrderByColumn::DESC).
	 * @return QueryColumn
	 */
	public function createCustomOrderByColumn($sql)
	{
		return new CustomOrderByColumn($sql, $this);
	}

}


/**
 * Interface that describes the basic methods that must be present in a QueryColumn.
 */
interface QueryColumn {
	
	/**
	 * Gets the QueryTable that created this QueryColumn.
	 * @return QueryTable
	 */
	public function getQueryTable();
	
	/**
	 * Gets the qualified SQL for this column.
	 * Usually qualified SQL means "tablealias.colname"; however, it could
	 * also be something more elaborate in the case of the custom SQL columns.
	 * @return string
	 */
	public function getQualifiedSql();
	
}

/**
 * Interface that describes the basic methods that must be present in an OrderByColumn.
 */
interface OrderByColumn extends QueryColumn {

	const ASC = 'ASC';
	const DESC = 'DESC';
	
	/**
	 * Sets the direction for the ORDER BY clause.
	 * @param string $direction The sort direction - OrderByColumn::ASC or OrderByColumn::DESC.
	 */  
	public function setDirection($direction);
	
	/**
	 * Gets the direction for the ORDER BY clause.
	 * @return string The sort direction - OrderByColumn::ASC or OrderByColumn::DESC.
	 */
	public function getDirection();

}

/**
 * A QueryColumn that corresponds to an actual column in the database (as opposed
 * to, e.g., a SQL expression serving column).
 */
class ActualQueryColumn implements QueryColumn {

	private $queryTable;
	private $columnMap;
	
	/**
	 * Construct a new QueryColumn for a QueryTable and from a ColumnMap.
	 * @param ColumnMap $columnMap
	 * @param QueryTable $queryTable
	 * @see QueryTable#createQueryColumn()
	 */
	public function __construct(ColumnMap $columnMap, QueryTable $queryTable)
	{
		$this->columnMap = $columnMap;
		$this->queryTable = $queryTable;
	}
	
	/**
	 * Gets the QueryTable that created this ActualQueryColumn.
	 * @return QueryTable
	 */ 
	public function getQueryTable()
	{
		return $this->queryTable;
	}

	/**
	 * Gets the ColumnMap for this query column.
	 * @return QueryTable
	 */
	public function getColumnMap()
	{
		return $this->columnMap;
	}
	
	/**
	 * Gets the qualified SQL for this query column.
	 * The qualified SQL for an ActualQueryColumn is of the form "table-alias-or-name.column-name".
	 * @return string
	 */
	public function getQualifiedSql()
	{
		return $this->queryTable->getAliasOrName() . '.' . $this->columnMap->getName();
	}
}


/**
 * An OrderByColumn that corresponds to an actual column in the database (as opposed
 * to, e.g., a SQL expression serving column).
 */
class ActualOrderByColumn extends ActualQueryColumn implements OrderByColumn {
	
	private $direction;
	
	/**
	 * Construct a new OrderByColumn for a QueryTable and from a ColumnMap and direction.
	 * @param ColumnMap $columnMap
	 * @param QueryTable $queryTable
	 * @param string $direction OrderByColumn::ASC or OrderByColumn::DESC 
	 * @see QueryTable#createQueryColumn()
	 */
	public function __construct(ColumnMap $columnMap, QueryTable $queryTable, $direction)
	{
		parent::__construct($columnMap, $queryTable);
		$this->direction = $direction;		
	}
	
	/**
	 * @see OrderByColumn#setDirection()
	 */
	public function setDirection($direction)
	{
		$this->direction = $direction;
	}

	/**
	 * @see OrderByColumn#getDirection()
	 */
	public function getDirection()
	{
		return $this->direction;
	}
}

/**
 * A QueryColumn that is a custom SQL expression (as opposed to an actual column
 * in the table).
 */
class CustomQueryColumn implements QueryColumn {

	private $queryTable;
	private $sql;
	
	/**
	 * Construct a new CustomQueryColumn with custom SQL for a QueryTable.
	 * @param string $sql
	 * @param QueryTable $queryTable
	 * @see QueryTable#createCustomQueryColumn()
	 */
	public function __construct($sql, QueryTable $queryTable)
	{
		$this->sql = $sql;
		$this->queryTable = $queryTable;
	}
	
	/**
	 * Gets the QueryTable that created this CustomQueryColumn.
	 * @return QueryTable
	 */ 
	public function getQueryTable()
	{
		return $this->queryTable;
	}
	
	/**
	 * Gets the qualified SQL for this query column.
	 * 
	 * The qualified SQL for a CustomQueryColumn is the SQL with any %1 replaced
	 * (using sprintf()) with the table alias or name.
	 * 
	 * @return string
	 */
	public function getQualifiedSql()
	{
		return sprintf($this->sql, $this->queryTable->getAliasOrName());
	}
}


/**
 * An OrderByColumn that is a custom SQL expression (as opposed to an actual column
 * in the table).
 */
class CustomOrderByColumn extends CustomQueryColumn implements OrderByColumn {

	private $direction;
	
	/**
	 * Construct a new CustomOrderByColumn with custom SQL for a QueryTable.
	 * @param string $sql
	 * @param QueryTable $queryTable
	 * @see QueryTable#createCustomOrderByColumn()
	 */
	public function __construct($sql, QueryTable $queryTable, $dir = null)
	{
		parent::__construct($sql, $queryTable);
		$this->direction = $dir;
	}
	
	/**
	 * @see OrderByColumn#setDirection()
	 */
	public function setDirection($direction)
	{
		$this->direction = $direction;
	}

	/**
	 * @see OrderByColumn#getDirection()
	 */
	public function getDirection()
	{
		return $this->direction;
	}
}


/**
 * Class to model a join between two tables.
 */
class Join {

	const IMPLICIT = "IMPLICIT";
	const LEFT = "LEFT JOIN";
	const RIGHT = "RIGHT JOIN";
	const INNER = "INNER JOIN";

    /** the left column of the join condition */
    private $leftColumn = null;

    /** the right column of the join condition */
    private $rightColumn = null;

    /** the type of the join (LEFT JOIN, ...), or null */
    private $joinType = null;

    /**
     * Create a new Join instance with left column, right column, and join type.
     *
     * @param QueryColumn $leftColumn the left column of the join condition;
     *        might contain an alias name
     * @param QueryColumn $rightColumn the right column of the join condition
     *        might contain an alias name
     * @param string $joinType the type of the join. Valid join types are
     *        null (adding the join condition to the where clause),
     *        Join::IMPLICIT (default), Join::LEFT, Join::RIGHT, and Join::INNER
     *
     */
    public function __construct(QueryColumn $leftCol, QueryColumn $rightCol, $joinType = self::IMPLICIT)
    {
	    $this->leftColumn = $leftCol;
	    $this->rightColumn = $rightCol;
	    $this->joinType = $joinType;
    }

    /**
     * Gets the type of the join (Join::IMPLICIT, Join::INNER, Join::LEFT, Join::RIGHT).
     * @return string
     */
    public function getJoinType()
    {
	    return $this->joinType;
    }

    /**
     * Get the left column of this join.
     * @return QueryColumn
     */
    public function getLeftColumn()
    {
	    return $this->leftColumn;
    }

    /**
     * Convenience method to get the QueryTable of the left column in this join.
     * @return QueryTable
     */
    public function getLeftTable()
	{
    	return $this->leftColumn->getQueryTable();
    }

    /**
     * Get the right column of this join condition. 
     * @return QueryColumn
     */
    public function getRightColumn()
    {
	    return $this->rightColumn;
    }

    /**
     * Convenience method to get the QueryTable of the right column in this join.
     * @return QueryTable
     */
    public function getRightTable()
	{
    	return $this->rightColumn->getQueryTable();
    }

    /**
     * Returns a string representation of the class for debugging purposes.
     * @return string A string representation of the class
     */
    public function toString()
    {
        return $this->joinType . ": " . 
				$this->leftColumn->getQualifiedSql() . "=" . $this->rightColumn->getQualifiedSql() . 
				" (ignoreCase not considered)";
    }
}
