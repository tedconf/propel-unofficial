<?php

/*
 *  $Id: PropelCollection.php $
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
 * Class for iterating over a list of Propel elements
 *
 * @author     Francois Zaninotto
 * @package    propel.formatter
 */
class PropelCollection extends ArrayObject
{
	protected $model = '';
	protected $iterator;

	// Generic Collection methods
	
	/**
	 * Get the data in the collection
	 *
	 * @return    array
	 */
	public function getData()
	{
		return $this->getArrayCopy();
	}

	/**
	 * Set the data in the collection
	 *
	 * @param    array $data
	 */
	public function setData($data)
	{
		$this->exchangeArray($data);
	}	
	
	/**
	 * Get all keys of the data in the collection
	 *
	 * @return    array
	 */
	public function getKeys()
	{
		return array_keys($this->getArrayCopy());
	}

	/**
	 * Gets the position of the internal pointer
	 * This position can be later used in seek()
	 *
	 * @return    int
	 */
	public function getPosition()
	{
		$key = $this->getIterator()->key();
		if ($key === null) {
			return 0;
		}
		$this->getIterator()->rewind();
		$lastPosition = 0;
		while ($this->getIterator()->key() != $key) {
			$lastPosition++;
			$this->getIterator()->next();
		}
		
		return $lastPosition;
	}

	/**
	 * Move the internal pointer to the beginning of the list
	 * And get the first element in the collection
	 *
	 * @return    mixed
	 */
	public function getFirst()
	{
		$this->getIterator()->rewind();
		return $this->getCurrent();
	}
	
	/**
	 * Check whether the internal pointer is at the beginning of the list
	 *
	 * @return boolean
	 */
	public function isFirst()
	{
		return $this->getPosition() == 0;
	}

	/**
	 * Move the internal pointer backward
	 * And get the previous element in the collection
	 *
	 * @return    mixed
	 */
	public function getPrevious()
	{
		$key = $this->getIterator()->key();
		$this->getIterator()->rewind();
		$lastPosition = -1;
		while ($this->getIterator()->key() != $key) {
			$lastPosition++;
			$this->getIterator()->next();
		}
		if ($lastPosition == -1) {
			return null;
		} else {
			$this->getIterator()->seek($lastPosition);
			return $this->getCurrent();
		}
	}

	/**
	 * Get the current element in the collection
	 *
	 * @return    mixed
	 */
	public function getCurrent()
	{
		return $this->getIterator()->current();
	}

	/**
	 * Get the key of the current element in the collection
	 *
	 * @return    mixed
	 */
	public function getKey()
	{
		return $this->getIterator()->key();
	}

	/**
	 * Move the internal pointer forward
	 * And get the next element in the collection
	 *
	 * @return    mixed
	 */
	public function getNext()
	{
		$this->getIterator()->next();
		return $this->getCurrent();
	}

	/**
	 * Move the internal pointer to the end of the list
	 * And get the last element in the collection
	 *
	 * @return    mixed
	 */
	public function getLast()
	{
		$this->getIterator()->rewind();
		$lastPosition = -1;
		while ($this->getIterator()->valid()) {
			$lastPosition++;
			$this->getIterator()->next();
		}
		if ($lastPosition == -1) {
			return null;
		} else {
			$this->getIterator()->seek($lastPosition);
			return $this->getCurrent();
		}
	}

	/**
	 * Check whether the internal pointer is at the end of the list
	 *
	 * @return boolean
	 */
	public function isLast()
	{
		$count = $this->count();
		if ($count == 0) {
			// empty list... so yes, this is the last
			return true;
		} else {
			return $this->getPosition() == $count - 1;
		}
	}

	/**
	 * Check if the collection is empty
	 */
	public function isEmpty()
	{
		return $this->count() == 0;
	}
	
	/**
	 * Check if the current index is an odd integer
	 *
	 * @return    boolean
	 */
	public function isOdd()
	{
		return (boolean) ($this->getIterator()->key() % 2);
	}

	/**
	 * Check if the current index is an even integer
	 *
	 * @return    boolean
	 */
	public function isEven()
	{
		return !$this->isOdd();
	}

	/**
	 * Get an element from its key
	 * Alias for ArrayObject::offsetGet()
	 *
	 * @param     mixed $key
	 *
	 * @return    mixed The element
	 */
	public function get($key)
	{
		if (!$this->offsetExists($key)) {
			throw new PropelException('Unknown offset ' . $offset);
		}
		return $this->offsetGet($key);
	}
	
	/**
	 * Pops an element off the end of the collection
	 *
	 * @return    mixed The popped element
	 */
	public function pop()
	{
		$ret = $this->getLast();
		$lastKey = $this->getIterator()->key();
		$this->offsetUnset((string) $lastKey);
		return $ret;
	}

	/**
	 * Pops an element off the beginning of the collection
	 *
	 * @return    mixed The popped element
	 */
	public function shift()
	{
		// the reindexing is complicated to deal with through the iterator
		// so let's use the simple solution
		$arr = $this->getArrayCopy();
		$ret = array_shift($arr);
		$this->exchangeArray($arr);
		
		return $ret;
	}

	/**
	 * Add an element to the collection with the given key
	 * Alias for ArrayObject::offsetSet()
	 *
	 * @param     mixed $key
	 * @param     mixed $value
	 */
	public function set($key, $value)
	{
		return $this->offsetSet($key, $value);
	}
	
	/**
	 * Removes a specified collection element
	 * Alias for ArrayObject::offsetUnset()
	 *
	 * @param     mixed $key
	 *
	 * @return    mixed The removed element
	 */
	public function remove($key)
	{
		if (!$this->offsetExists($key)) {
			throw new PropelException('Unknown offset ' . $offset);
		}
		return $this->offsetUnset($key);
	}
	
	/**
	 * Clears the collection
	 * 
	 * @return    array The previous collection
	 */
	public function clear()
	{
		return $this->exchangeArray(array());
	}

	/**
	 * Whether or not this collection contains a specified element
	 * Alias for ArrayObject::offsetExists()
	 *
	 * @param      mixed $key the key of the element
	 *
	 * @return     boolean
	 */
	public function contains($key)
	{
		return $this->offsetExists($key);
	}

	/**
	 * Search an element in the collection
	 *
	 * @param     mixed $element 
	 *
	 * @return    mixed Returns the key for the element if it is found in the collection, FALSE otherwise
	 */
	public function search($element)
	{
		return array_search($element, $this->getArrayCopy(), true);
	}	
	
	// Serializable interface
	
	public function serialize()
	{
		$repr = array(
			'data'   => $this->getArrayCopy(),
			'model'  => $this->model,
		);
		return serialize($repr);
	}
	
	public function unserialize($data)
	{
		$repr = unserialize($data);
		$this->exchangeArray($repr['data']);
		$this->model = $repr['model'];
	}
	
	// IteratorAggregate method
	
	/**
	 * Overrides ArrayObject::getIterator() to return always the same iterator object
	 * Instead of a new instance for each call
	 */
	public function getIterator()
	{
		if (null === $this->iterator) {
			$this->iterator = parent::getIterator();
		}
		return $this->iterator;
	}
	
	// Propel collection methods
	
	/**
	 * Set the model of the elements in the collection
	 *
	 * @param     string $model Name of the Propel object classes stored in the collection
	 */
	public function setModel($model)
	{
		$this->model = $model;
	}

	/**
	 * Get the model of the elements in the collection
	 *
	 * @return    string Name of the Propel object class stored in the collection
	 */
	public function getModel()
	{
		return $this->model;
	}
	
	/**
	 * Get the peer class of the elements in the collection
	 *
	 * @return    string Name of the Propel peer class stored in the collection
	 */
	public function getPeerClass()
	{
		if ($this->model == '') {
			throw new PropelException('You must set the collection model before interacting with it');
		}
		return constant($this->getModel() . '::PEER');
	}

	/**
	 * Get a connection object for the database containing the elements of the collection
	 *
	 * @param     string $type The connection type (Propel::CONNECTION_READ by default; can be Propel::connection_WRITE)
	 *
	 * @return    PropelPDO a connection object
	 */
	public function getConnection($type =  Propel::CONNECTION_READ)
	{
		$databaseName = constant($this->getPeerClass() . '::DATABASE_NAME');
		
		return Propel::getConnection($databaseName, $type);
	}
	
}

?>