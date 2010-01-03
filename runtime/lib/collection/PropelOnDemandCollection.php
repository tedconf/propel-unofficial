<?php

/*
 *  $Id: PropelOnDemandCollection.php $
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
 * Class for iterating over a statement and returning one Propel object at a time
 *
 * @author     Francois Zaninotto
 * @package    propel.collection
 */
class PropelOnDemandCollection extends PropelCollection implements Iterator
{
	protected 
		$stmt, 
		$formatter, 
		$currentRow, 
		$currentKey = -1,
		$isValid = true;
	
	public function setStatement(PDOStatement $stmt)
	{
		$this->stmt = $stmt;
	}

	public function setFormatter(PropelFormatter $formatter)
	{
		$this->formatter = $formatter;
	}
	
	// IteratorAggregate Interface
	
	public function getIterator()
	{
		return $this;
	}

	// Iterator Interface
	
	public function current()
	{
		return $this->formatter->getAllObjectsFromRow($this->currentRow);
	}
	
	public function key()
	{
		return $this->currentKey;
	}
	
	public function next()
	{
		$this->currentRow = $this->stmt->fetch(PDO::FETCH_NUM);
		$this->currentKey++;
		$this->isValid = (boolean) $this->currentRow;
		if (!$this->isValid) {
			$this->stmt->closeCursor();
		}
	}
	
	public function rewind()
	{
		// initialize the current row and key
		$this->next();
	}
	
	public function valid()
	{
		return $this->isValid;
	}

	// ArrayAccess Interface
	
	public function offsetExists($offset)
	{
		if ($offset == $this->currentKey) {
			return true;
		}
		throw new PropelException('The On Demand Collection does not allow acces by offset');
	}

	public function offsetGet($offset)
	{
		if ($offset == $this->currentKey) {
			return $this->currentRow;
		}
		throw new PropelException('The On Demand Collection does not allow acces by offset');
	}
	
	public function offsetSet($offset, $value)
	{
		throw new PropelException('The On Demand Collection is read only');
	}

	public function offsetUnset($offset)
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	// Serializable Interface
	
	public function serialize()
	{
		throw new PropelException('The On Demand Collection cannot be serialized');
	}

	public function unserialize($data)
	{
		throw new PropelException('The On Demand Collection cannot be serialized');
	}
	
	// Countable Interface
	
	public function count()
	{
		return $this->stmt->rowCount();
	}
	
	// ArrayObject methods
	
	public function append($value)
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function asort()
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function exchangeArray($input)
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function getArrayCopy()
	{
		throw new PropelException('The On Demand Collection does not allow acces by offset');
	}
	
	public function getFlags()
	{
		throw new PropelException('The On Demand Collection does not allow acces by offset');
	}
	
	public function ksort()
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function natcasesort()
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function natsort()
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function setFlags($flags)
	{
		throw new PropelException('The On Demand Collection does not allow acces by offset');
	}
	
	public function uasort($cmp_function)
	{
		throw new PropelException('The On Demand Collection is read only');
	}
	
	public function uksort($cmp_function)
	{
		throw new PropelException('The On Demand Collection is read only');
	}
}