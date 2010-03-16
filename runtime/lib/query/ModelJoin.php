<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * A ModelJoin is a Join object tied to a RelationMap object
 *
 * @author     Francois Zaninotto (Propel)
 * @package    propel.runtime.query
 */
class ModelJoin extends Join
{
	protected $relationMap;
	protected $tableMap;
	protected $previousJoin;
	protected $relationAlias;

	public function setRelationMap(RelationMap $relationMap, $leftTableAlias = null, $rightTableAlias = null)
	{
		$leftCols = $relationMap->getLeftColumns();
		$rightCols = $relationMap->getRightColumns();
		$nbColumns = $relationMap->countColumnMappings();
		for ($i=0; $i < $nbColumns; $i++) {
			$leftColName  = ($leftTableAlias ? $leftTableAlias  : $leftCols[$i]->getTableName()) . '.' . $leftCols[$i]->getName();
			$rightColName = ($rightTableAlias ? $rightTableAlias : $rightCols[$i]->getTableName()) . '.' . $rightCols[$i]->getName();
			$this->addCondition($leftColName, $rightColName, Criteria::EQUAL);
		}
		$this->relationMap = $relationMap;
		if (null !== $rightTableAlias) {
			$this->setRelationAlias($rightTableAlias);
		}
		
		return $this;
	}
	
	public function getRelationMap()
	{
		return $this->relationMap;
	}

	/**
	 * Sets the right tableMap for this join
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
	 * Gets the right tableMap for this join
	 * 
	 * @return TableMap The table map
	 */
	public function getTableMap()
	{
		if (null === $this->tableMap && null !== $this->relationMap)
		{
			$this->tableMap = $this->relationMap->getRightTable();
		}
		return $this->tableMap;
	}
		
	public function setPreviousJoin(ModelJoin $join)
	{
		$this->previousJoin = $join;
		
		return $this;
	}
	
	public function getPreviousJoin()
	{
		return $this->previousJoin;
	}
	
	public function isPrimary()
	{
		return null === $this->previousJoin;
	}

	public function setRelationAlias($relationAlias)
	{
		$this->relationAlias = $relationAlias;
		
		return $this;
	}
	
	public function getRelationAlias()
	{
		return $this->relationAlias;
	}
	
	public function hasRelationAlias()
	{
		return null !== $this->relationAlias;
	}

	public function getObjectToRelate($startObject)
	{
		if($this->isPrimary()) {
			return $startObject;
		} else {
			$previousJoin = $this->getPreviousJoin();
			$previousObject = $previousJoin->getObjectToRelate($startObject);
			$method = 'get' . $previousJoin->getRelationMap()->getName();
			return $previousObject->$method();
		}
	}

	public function equals($join)
	{
		return parent::equals($join)
			&& $this->relationMap == $join->getRelationMap()
			&& $this->previousJoin == $join->getPreviousJoin()
			&& $this->relationAlias == $join->getRelationAlias();
	}
	
	public function __toString()
	{
		return parent::toString()
			. ' tableMap: ' . ($this->tableMap ? get_class($this->tableMap) : 'null')
			. ' relationMap: ' . $this->relationMap->getName()
			. ' previousJoin: ' . ($this->previousJoin ? '(' . $this->previousJoin . ')' : 'null')
			. ' relationAlias: ' . $this->relationAlias;
	}
}
 