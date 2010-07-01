<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Data object to describe a join between two tables, for example
 * <pre>
 * table_a LEFT JOIN table_b ON table_a.id = table_b.a_id
 * </pre>
 *
 * @author     Leon van der Ree
 * @package    propel.runtime.query
 */
class CriterionJoin extends Join
{
	protected
		$leftTableName,
		$leftTableAlias,
		$rightTableName,
		$rightTableAlias,
	    $tableMap,
		$condition;

	/**
	 * @param     string $leftTableName the left table of the join condition
	 */
	public function setLeftTableName($leftTableName, $leftTableAlias = null)
	{
		$this->leftTableName = $leftTableName;
		$this->setLeftTableAlias($leftTableAlias);
		
		return $this;
	}
	
	/**
	 * @return     string the left table of the join condition
	 */
	public function getLeftTableName()
	{
		return $this->leftTableName;
	}
	
	public function setLeftTableAlias($leftTableAlias)
	{
		$this->leftTableAlias = $leftTableAlias;
		
		return $this;
	}
	
	/**
	 * @return     string the alias of the left table of the join condition
	 */
	public function getLeftTableAlias()
	{
		return $this->leftTableAlias;
	}
	
	public function hasLeftTableAlias()
	{
		return null !== $this->leftTableAlias;
	}

	/**
	 * @return     string the left table name and alias
	 */
	public function getLeftTableAndAlias()
	{
		return null === $this->leftTableAlias ? $this->leftTableName : ($this->leftTableName . ' ' . $this->leftTableAlias);
	}

	/**
	 * @param     string $rightTableName the right table of the join condition
	 */
	public function setRightTableName($rightTableName, $rightTableAlias = null)
	{
		$this->rightTableName = $rightTableName;
		$this->setRightTableAlias($rightTableAlias);
		
		return $this;
	}
	
	/**
	 * @return     string the right table of the join condition
	 */
	public function getRightTableName()
	{
		return $this->rightTableName;
	}
	
	public function setRightTableAlias($rightTableAlias)
	{
		$this->rightTableAlias = $rightTableAlias;
		
		return $this;
	}
	
	public function getRightTableAlias()
	{
		return $this->rightTableAlias;
	}
	
	public function hasRightTableAlias()
	{
		return null !== $this->rightTableAlias;
	}
	
	/**
	 * @param      array &$params Parameters that are to be replaced in prepared statement. (required when populating criteria with subquery in join)
	 * @return     string the right table name and alias
	 */
	public function getRightTableAndAlias(&$params = array())
	{
		if ($this->rightTableName instanceof ModelCriteria)
		{
			return '(' . BasePeer::createSelectSql($this->rightTableName, $params) . ') AS '.$this->rightTableName->getSubQueryAlias();
		} else {
			return null === $this->getRightTableAlias() ? $this->getRightTableName() : ($this->getRightTableName() . ' ' . $this->getRightTableAlias());
		}
	}
	
	/**
	 * Sets the related tableMap for this join
	 * 
	 * @param TableMap $tableMap The table map to use
	 * 
	 * @return ModelJoin The current join object, for fluid interface
	 */
	public function setTableMap(TableMap $tableMap)
	{
		$this->tableMap = $tableMap;
		
		return $this;
	}

	/**
	 * Gets the related tableMap for this join
	 * 
	 * @return TableMap The table map
	 */
	public function getTableMap()
	{
		if (null === $this->tableMap) {
		  throw new PropelException('No TableMap defined for this join');
		}
		
		return $this->tableMap;
	}	
	
	/**
	 *  sets the join condition between the two tables
	 *  
	 *  @param Criterion $condition  the condition two join the two tables on
	 */
	public function setCondition(Criterion $condition)
	{
		$this->condition = $condition;
		
		return $this;
	}
	
	/**
	 * returns the condition to join the two tables
	 * 
	 * @return Criterion The main criterion to join the tables on
	 * @throws PropelException When no condition is defined a PropelException is thrown
	 */
	public function getCondition()
	{
		if (null === $this->condition) {
			throw new PropelException('No condition defined on the join');
		}
		
		return $this->condition;
	}
	
	/**
	 * returns the joinType of this JoinObject (default to INNER JOIN)
	 * 
	 * @return string the joinType (default to INNER JOIN) 
	 */
	public function getJoinType() 
	{
		$joinType = parent::getJoinType();
		
		if (!$joinType) {
			$joinType = Criteria::INNER_JOIN;
		} 
		
		
		return $joinType; 
	}
	
	
	/**
	 * returns the sql-clause for this join
	 * 
	 * @param array &$params an array with values that is used during the binding of the query
	 * @return string  the clause for this join
	 */
	public function getClause(&$params)
	{
		$sql = '';
		$this->getCondition()->appendPsTo($sql, $params);
		
		return sprintf('%s %s ON (%s)', $this->getJoinType(), $this->getRightTableAndAlias(), $sql);
	}
	
	/**
	 * 
	 * Tests if this Join is equal to the provided Join
	 * 
	 * @param Join $join  
	 */
	public function equals($join)
	{
		return $join instanceof CriterionJoin 
				&& $this->getLeftTableName()   == $join->getLeftTableName()
				&& $this->getLeftTableAlias()  == $join->getLeftTableAlias()
				&& $this->getRightTableName()  == $join->getRightTableName()
				&& $this->getRightTableAlias() == $join->getRightTableAlias()
				&& $this->getCondition()->equals($join->getCondition())
				&& $this->getJoinType()        == $join->getJoinType();
	}
	
	/**
	 * returns a String representation of the class,
	 * mainly for debugging purposes, use getClause($params) for queries 
	 *
	 * @return string     A String representation of the class
	 */
	public function __toString()
	{
		$params = array();
		return $this->getClause($params);
	}
}
