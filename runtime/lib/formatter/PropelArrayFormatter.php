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
 * Array formatter for Propel query
 * format() returns a PropelArrayCollection of associative arrays
 *
 * @author     Francois Zaninotto
 * @version    $Revision$
 * @package    propel.runtime.formatter
 */
class PropelArrayFormatter extends PropelFormatter
{
	protected $collectionName = 'PropelArrayCollection';
	
	public function format(PDOStatement $stmt)
	{
		$this->checkCriteria();
		$class = $this->collectionName;
		if(class_exists($class)) {
			$collection = new $class();
			$collection->setModel($this->getCriteria()->getModelName());
			$collection->setFormatter($this);
		} else {
			$collection = array();
		}
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$collection[] = $this->getStructuredArrayFromRow($row);
		}
		$this->currentObjects = array();
		$stmt->closeCursor();
		
		return $collection;
	}

	public function formatOne(PDOStatement $stmt)
	{
		$this->checkCriteria();
		if ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$result = $this->getStructuredArrayFromRow($row);
		} else {
			$result = null;
		}
		$this->currentObjects = array();
		$stmt->closeCursor();
		return $result;
	}

	public function isObjectFormatter()
	{
		return false;
	}
	

	/**
	 * Hydrates a series of objects from a result row
	 * The first object to hydrate is the model of the Criteria
	 * The following objects (the ones added by way of ModelCriteria::with()) are linked to the first one
	 *
	 *  @param    array  $row associative array indexed by column number,
	 *                   as returned by PDOStatement::fetch(PDO::FETCH_NUM)
	 *
	 * @return    Array
	 */
	public function getStructuredArrayFromRow($row)
	{
		$col = 0;
		$mainObjectArray = $this->getSingleObjectFromRow($row, $this->class, $col)->toArray();
		foreach ($this->getCriteria()->getWith() as $join) {
			$secondaryObject = $this->getSingleObjectFromRow($row, $join->getTableMap()->getClassname(), $col);
			if ($secondaryObject->isPrimaryKeyNull()) {
				$secondaryObjectArray = array();
			} else {
				$secondaryObjectArray = $secondaryObject->toArray();
			}
			$arrayToAugment = &$mainObjectArray;
			if (!$join->isPrimary()) {
				$prevJoin = $join;
				while($prevJoin = $prevJoin->getPreviousJoin()) {
					$arrayToAugment = &$arrayToAugment[$prevJoin->getRelationMap()->getName()];
				}
			}
			$arrayToAugment[$join->getRelationMap()->getName()] = $secondaryObjectArray;
		}
		foreach ($this->getCriteria()->getAsColumns() as $alias => $clause) {
			$mainObjectArray[$alias] = $row[$col];
			$col++;
		}
		return $mainObjectArray;
	}

}