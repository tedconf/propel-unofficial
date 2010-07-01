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
class ModelJoin extends CriterionJoin
{
	protected $relationMap;
	protected $previousJoin;

	/**
	 * 
	 * Enter description here ...
	 * @param Criteria $criteria
	 * @param RelationMap $relationMap
	 * @param unknown_type $leftTableAlias
	 * @param unknown_type $relationAlias
	 * @param Criterion $extraJoinCriterion TODO: add unit-test
	 */
	public function setRelationMap(Criteria $criteria, RelationMap $relationMap, $leftTableAlias = null, $relationAlias = null, Criterion $extraJoinCriterion = null)
	{
		$this->relationMap = $relationMap;
		$this->setLeftTableName($relationMap->getLeftTable()->getName(), $leftTableAlias);
		$this->setRightTableName($relationMap->getRightTable()->getName(), $relationAlias);
		
		// get default JoinConditions
		$leftCols = $relationMap->getLeftColumns();
		$rightCols = $relationMap->getRightColumns();
		$nbColumns = $relationMap->countColumnMappings();
		
		// build criterions
		$criterions = array();
		for ($i=0; $i < $nbColumns; $i++) {
			$leftColName  = ($leftTableAlias ? $leftTableAlias  : $leftCols[$i]->getTableName()) . '.' . $leftCols[$i]->getName();
			$rightColName = ($relationAlias ? $relationAlias : $rightCols[$i]->getTableName()) . '.' . $rightCols[$i]->getName();
			
			$clause = $leftColName.Criteria::EQUAL.$rightColName;
			$criterions[] = new Criterion($criteria, null, $clause, Criteria::CUSTOM); 
		}
		// "and" all criterions together
		$firstCriterion = array_shift($criterions);
		foreach ($criterions as $criterion) {
			$firstCriterion->addAnd($criterion);
		}
		// add aditional extra JoinCriterion
		if (null !== $extraJoinCriterion) {
			$firstCriterion->addAnd($extraJoinCriterion);
		}
		// set the default join-condition
		$this->setCondition($firstCriterion);
		
		return $this;
	}
	
	// TODO: maybe we want to remove this proxy...
	public function setRelationAlias($alias)
	{
		$this->setRightTableAlias($alias);
	}
	
	public function getRelationMap()
	{
		return $this->relationMap;
	}

	/**
	 * Gets the related tableMap for this join
	 * 
	 * @return TableMap The table map
	 */
	public function getTableMap()
	{
		if (null === $this->tableMap && null !== $this->relationMap)
		{
			$this->setTableMap($this->relationMap->getRightTable());
		}
		
		return parent::getTableMap();
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

	/**
	 * This method returns the last related, but already hydrated object up until this join
	 * Starting from $startObject and continuously calling the getters to get 
	 * to the base object for the current join.
	 * 
	 * This method only works if PreviousJoin has been defined,
	 * which only happens when you provide dotted relations when calling join
	 * 
	 * @param Object $startObject the start object all joins originate from and which has already hydrated
	 * @return Object the base Object of this join
	 */
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
		  && $this->getTableMap() == $join->getTableMap()
			&& $this->relationMap   == $join->getRelationMap()
			&& $this->previousJoin  == $join->getPreviousJoin();
	}
	
	public function __toString()
	{
		return parent::__toString()
			. ' tableMap: ' . ($this->tableMap ? get_class($this->tableMap) : 'null')
			. ' relationMap: ' . $this->relationMap->getName()
			. ' previousJoin: ' . ($this->previousJoin ? '(' . $this->previousJoin . ')' : 'null');
	}
}
 