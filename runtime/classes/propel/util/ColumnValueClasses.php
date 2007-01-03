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
 * This file contains a several classes that represent column-value pairs.
 *
 * These classes are used by the Expression classes to store column-value pairs
 * that will later be bound to the queries using methods like
 * PDOStatement->bindValue().
 */

/**
 * A class that holds a value whic is associated with a particular column.
 *
 * This is used by the Expression#buildSql() method to add values to the passed-in array, and
 * then by BasePeer::populateStmtValues2() to bind values to PDO statements.
 */
class ColumnValue {

	private $column;
	private $value;

	/**
	 *
	 * @param ColumnMap $col
	 * @param mixed $value
	 */
	public function __construct(ColumnMap $col, $value)
	{
		$this->column = $col;
		$this->value = $value;
	}

	/**
	 * Gets the ColumnMap associated with this value.
	 * @return ColumnMap
	 */
	public function getColumnMap()
	{
		return $this->column;
	}

	/**
	 * Sets the ColumnMap associated with this value.
	 * @param ColumnMap $col
	 */
	public function setColumnMap(ColumnMap $col)
	{
		$this->column = $col;
	}

	/**
	 * Gets the value.
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Sets the value.
	 * @param mixed $val
	 */
	public function setValue($val)
	{
		$this->value = $val;
	}

}

/**
 * This class represents a collection of ColumnValue objects for a single table.
 *
 * This class is used by methods like doInsert() to store a collection of ColumnValue
 * objects for insertion.
 */
class ColumnValueCollection implements IteratorAggregate {

	private $tableMap;
	private $columnValues = array();

	/**
	 * Constructs a new collection for the specified table.
	 * @param TableMap $table A TableMap
	 */
	public function __construct(TableMap $table)
	{
		$this->tableMap = $table;
	}

	/**
	 * Adds an already-configured ColumnValue object to the collection.
	 */
	public function addColumnValue(ColumnValue $cv)
	{
		$this->columnValues[$cv->getColumnMap()->getName()] = $cv;
	}

	/**
	 * Creates a ColumnValue object for the given columname (resolved agains this collection's TableMap) and adds it to the collection.
	 * @param string $colname The name of the column.
	 * @param mixed $value The value.
	 */
	public function add($colname, $value)
	{
		$colMap = $this->tableMap->getColumn($colname);
		if (!$colMap) {
			throw new PropelException("Unable to load ColumnMap for column [" . $colname . "]");
		}
		$this->columnValues[$colname] = new ColumnValue($colMap, $value); // we could call ->add() but this is a tad quicker
	}

	/**
	 * Gets a ColumnValue for specified column name from the collection.
	 * @return ColumnValue
	 */
	public function get($colname)
	{
		if (!isset($this->columnValues[$colname])) {
			return null;
		}
		return $this->columnValues[$colname];
	}

	/**
	 * Removes a ColumnValue for specified column name from the collection.
	 * @return ColumnValue The removed ColumnValue object.
	 */
	public function remove($colname)
	{
		$val = null;
		if (isset($this->columnValues[$colname])) {
			$val = $this->columnValues[$colname];
			unset($this->columnValues[$colname]);
		}
		return $val;
	}

	/**
	 * Returns the column names of the ColumnValue objects in the collection.
	 */
	public function keys()
	{
		return array_keys($this->columnValues);
	}

	/**
	 * Whether the collection contains a ColumnValue object with the given column name.
	 * @return boolean
	 */
	public function containsKey($colname)
	{
		return array_key_exists($colname, $this->columnValues);
	}

	/**
	 * Gets the number of elements in this collection.
	 * @return int
	 */
	public function size()
	{
		return count($this->columnValues);
	}

	/**
	 * Gets the TableMap for this collection.
	 * @return TableMap
	 */
	public function getTableMap()
	{
		return $this->tableMap;
	}

	/**
	 * SPL IteratorAggregate method to return an Iterator for this object.
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new ColumnValueCollectionIterator($this);
	}
}

/**
 * Class that implements SPL Iterator interface for iterating over a ColumnValueCollection.
 */
class ColumnValueCollectionIterator implements Iterator {

	private $idx = 0;
	private $coll;
	private $keys;
	private $size;

	public function __construct(ColumnValueCollection $coll)
	{
		$this->coll = $coll;
		$this->keys = $coll->keys();
		$this->size = $coll->size();
	}

	public function rewind()
	{
		$this->idx = 0;
	}

	public function valid()
	{
		return $this->idx < $this->size;
	}

	public function key()
	{
		return $this->keys[$this->idx];
	}

	public function current()
	{
		return $this->coll->get($this->keys[$this->idx]);
	}

	public function next()
	{
		$this->idx++;
	}

	public function size()
	{
		return $this->size;
	}

}

/**
 * Utility class for ColumnValue objects and collections.
 */
class ColumnValueUtil {

	/**
	 * Debug method to get a an array containing map of column name to value.
	 * @param mixed $values Array or Collection.
	 * @return array
	 */
	public static function getValuesArray($values)
	{
		$map = array();
		$i = 0;
		foreach($values as $cv) {
			$map[$i++] = array('column'=>$cv->getColumnMap()->getFullyQualifiedName(), 'value'=>$cv->getValue());
		}
		return $map;
	}

}
