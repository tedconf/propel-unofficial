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

require_once 'propel/engine/builder/om/ObjectBuilder.php';

/**
 * Generates a PHP5 tree node Object class for user object model (OM) using Nested Set way.
 *
 * This class produces the base tree node object class (e.g. BaseMyTableNestedSet) which contains all
 * the custom-built accessor and setter methods.
 *
 * @author     Heltem <heltem@o2php.com>
 * @package    propel.engine.builder.om.php5
 */
class PHP5NestedSetBuilder extends ObjectBuilder {

	/**
	 * Gets the package for the [base] object classes.
	 * @return     string
	 */
	public function getPackage()
	{
		return parent::getPackage() . ".om";
	}

	/**
	 * Returns the name of the current class being built.
	 * @return     string
	 */
	public function getUnprefixedClassname()
	{
		return $this->getBuildProperty('basePrefix') . $this->getStubObjectBuilder()->getClassname() . 'NestedSet';
	}

	/**
	 * Adds the include() statements for files that this class depends on or utilizes.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addIncludes(&$script)
	{
		$script .="
require '".$this->getObjectBuilder()->getClassFilePath()."';
";
	} // addIncludes()

	/**
	 * Adds class phpdoc comment and openning of class.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addClassOpen(&$script)
	{

		$table = $this->getTable();
		$tableName = $table->getName();
		$tableDesc = $table->getDescription();

		$script .= "
/**
 * Base class that represents a row from the '$tableName' table.
 *
 * $tableDesc
 *";
		if ($this->getBuildProperty('addTimeStamp')) {
			$now = strftime('%c');
			$script .= "
 * This class was autogenerated by Propel " . $this->getBuildProperty('version') . " on:
 *
 * $now
 *";
		}
		$script .= "
 * @package    ".$this->getPackage()."
 */
abstract class ".$this->getClassname()." extends ".$this->getObjectBuilder()->getClassname()." implements BaseNodeObject {
";
	}

	/**
	 * Specifies the methods that are added as part of the basic OM class.
	 * This can be overridden by subclasses that wish to add more methods.
	 * @see        ObjectBuilder::addClassBody()
	 */
	protected function addClassBody(&$script)
	{
		$table = $this->getTable();

		$this->addAttributes($script);

		$this->addGetIterator($script);

		$this->addSave($script);
		$this->addDelete($script);

		$this->addGetLevel($script);
		$this->addSetLevel($script);

		$this->addSetChildren($script);
		$this->addSetParentNode($script);
		$this->addSetPrevSibling($script);
		$this->addSetNextSibling($script);

		$this->addGetPath($script);
		$this->addGetNumberOfChildren($script);
		$this->addGetNumberOfDescendants($script);

		$this->addGetChildren($script);
		$this->addGetDescendants($script);

		$this->addIsRoot($script);
		$this->addIsLeaf($script);
		$this->addIsEqualTo($script);

		$this->addHasParent($script);
		$this->addHasChildren($script);
		$this->addHasPrevSibling($script);
		$this->addHasNextSibling($script);

		$this->addRetrieveParent($script);
		$this->addRetrieveFirstChild($script);
		$this->addRetrieveLastChild($script);
		$this->addRetrievePrevSibling($script);
		$this->addRetrieveNextSibling($script);

		$this->addInsertAsFirstChildOf($script);
		$this->addInsertAsLastChildOf($script);

		$this->addInsertAsPrevSiblingOf($script);
		$this->addInsertAsNextSiblingOf($script);

		$this->addGetLeft($script);
		$this->addGetRight($script);
		$this->addGetScopeId($script);

		$this->addSetLeft($script);
		$this->addSetRight($script);
		$this->addSetScopeId($script);
	}

	/**
	 * Closes class.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addClassClose(&$script)
	{
		$script .= "
} // " . $this->getClassname() . "
";
	}


	/**
	 * Adds class attributes.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addAttributes(&$script)
	{
		$objectClassName = $this->getStubObjectBuilder()->getClassname();
		$script .= "
	/**
	 * Store level of node
	 * @var        int
	 */
	protected \$level = null;

	/**
	 * Store if node has prev sibling
	 * @var        bool
	 */
	protected \$hasPrevSibling = null;

	/**
	 * Store node if has prev sibling
	 * @var        $objectClassName
	 */
	protected \$prevSibling = null;

	/**
	 * Store if node has next sibling
	 * @var        bool
	 */
	protected \$hasNextSibling = null;

	/**
	 * Store node if has next sibling
	 * @var        $objectClassName
	 */
	protected \$nextSibling = null;

	/**
	 * Store if node has parent node
	 * @var        bool
	 */
	protected \$hasParentNode = null;

	/**
	 * The parent node for this node.
	 * @var        $objectClassName
	 */
	protected \$parentNode = null;

	/**
	 * Store children of the node
	 * @var        array
	 */
	protected \$_children = null;
";
	}

	protected function addGetIterator(&$script)
	{
		$script .= "
	/**
	 * Returns a pre-order iterator for this node and its children.
	 *
	 * @return     NodeIterator
	 */
	public function getIterator()
	{
		return new NestedSetRecursiveIterator(\$this);
	}
";
	}

	protected function addSave(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Saves modified object data to the datastore.
	 * If object is saved without left/right values, set them as undefined (0)
	 *
	 * @param      PDO Connection to use.
	 * @return     void
	 * @throws     PropelException
	 */
	public function save(PDO \$con = null)
	{
		\$left = \$this->getLeftValue();
		\$right = \$this->getRightValue();
		if (empty(\$left) || empty(\$right)) {
			\$root = $peerClassname::retrieveRoot(\$con);
			$peerClassname::insertAsLastChildOf(\$root, \$this, \$con);
		}

		parent::save(\$con);
	}
";
	}

	protected function addDelete(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Removes this object and all descendants from datastore.
	 *
	 * @param      PDO Connection to use.
	 * @return     void
	 * @throws     PropelException
	 */
	public function delete(PDO \$con = null)
	{
		// delete node first
		parent::delete(\$con);

		// delete descendants and then shift tree
		$peerClassname::deleteDescendants(\$this, \$con);
	}
";
	}

	protected function addGetLevel(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Gets the level if set, otherwise calculates this and returns it
	 *
	 * @param      PDO Connection to use.
	 * @return     int
	 */
	public function getLevel(PDO \$con = null)
	{
		if (null === \$this->level) {
			\$this->level = $peerClassname::getLevel(\$this, \$con);
		}
		return \$this->level;
	}
";
	}

	protected function addSetLevel(&$script)
	{
		$script .= "
	/**
	 * Sets the level of the node in the tree
	 *
	 * @param      int \$v new value
	 * @return     void
	 */
	public function setLevel(\$level)
	{
		\$this->level = \$level;
	}
";
	}

	protected function addSetChildren(&$script)
	{
		$objectClassName = $this->getStubObjectBuilder()->getClassname();
		$script .= "
	/**
	 * Sets the children array of the node in the tree
	 *
	 * @param      array of $objectClassName \$children	array of Propel node object
	 * @return     void
	 */
	public function setChildren(\$children)
	{
		\$this->_children = \$children;
	}
";
	}

	protected function addSetParentNode(&$script)
	{
		$objectClassName = $this->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Sets the parentNode of the node in the tree
	 *
	 * @param      $objectClassName \$node Propel node object
	 * @return     void
	 */
	public function setParentNode(\$node)
	{
		\$this->parentNode = (true === (\$this->hasParentNode = $peerClassname::isValid(\$node))) ? \$node : null;
	}
";
	}

	protected function addSetPrevSibling(&$script)
	{
		$objectClassName = $this->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Sets the previous sibling of the node in the tree
	 *
	 * @param      $objectClassName \$node Propel node object
	 * @return     void
	 */
	public function setPrevSibling(\$node)
	{
		\$this->prevSibling = \$node;
		\$this->hasPrevSibling = $peerClassname::isValid(\$node);
	}
";
	}

	protected function addSetNextSibling(&$script)
	{
		$objectClassName = $this->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Sets the next sibling of the node in the tree
	 *
	 * @param      $objectClassName \$node Propel node object
	 * @return     void
	 */
	public function setNextSibling(\$node)
	{
		\$this->nextSibling = \$node;
		\$this->hasNextSibling = $peerClassname::isValid(\$node);
	}
";
	}

	protected function addGetPath(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Get the path to the node in the tree
	 *
	 * @param      PDO Connection to use.
	 * @return     array
	 */
	public function getPath(PDO \$con = null)
	{
		return $peerClassname::getPath(\$this, \$con);
	}
";
	}

	protected function addGetNumberOfChildren(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Gets the number of children for the node (direct descendants)
	 *
	 * @param      PDO Connection to use.
	 * @return     int
	 */
	public function getNumberOfChildren(PDO \$con = null)
	{
		return $peerClassname::getNumberOfChildren(\$this, \$con);
	}
";
	}

	protected function addGetNumberOfDescendants(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Gets the total number of desceandants for the node
	 *
	 * @param      PDO Connection to use.
	 * @return     int
	 */
	public function getNumberOfDescendants(PDO \$con = null)
	{
		return $peerClassname::getNumberOfDescendants(\$node, \$con);
	}
";
	}

	protected function addGetChildren(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Gets the children for the node
	 *
	 * @param      PDO Connection to use.
	 * @return     array
	 */
	public function getChildren(PDO \$con = null)
	{
		\$this->getLevel();

		if (is_array(\$this->_children)) {
			return \$this->_children;
		}

		return $peerClassname::retrieveChildren(\$this, \$con);
	}
";
	}

	protected function addGetDescendants(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Gets the descendants for the node
	 *
	 * @param      PDO Connection to use.
	 * @return     array
	 */
	public function getDescendants(PDO \$con = null)
	{
		\$this->getLevel();
		if (is_array(\$this->_children)) {
			return \$this->_children;
		}

		return $peerClassname::retrieveDescendants(\$this, \$con);
	}
";
	}

	protected function addIsRoot(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Returns true if node is the root node of the tree.
	 *
	 * @return     bool
	 */
	public function isRoot()
	{
		return $peerClassname::isRoot(\$this);
	}
";
	}

	protected function addIsLeaf(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Return true if the node is a leaf node
	 *
	 * @return     bool
	 */
	public function isLeaf()
	{
		return $peerClassname::isLeaf(\$this);
	}
";
	}

	protected function addIsEqualTo(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Tests if object is equal to \$node
	 *
	 * @param      object \$node		Propel object for node to compare to
	 * @return     bool
	 */
	public function isEqualTo(BaseNodeObject \$node = null)
	{
		return $peerClassname::isEqualTo(\$this, \$node);
	}
";
	}

	protected function addHasParent(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Tests if object has an ancestor
	 *
	 * @param      PDO \$con      Connection to use.
	 * @return     bool
	 */
	public function hasParent(PDO \$con = null)
	{
		if (null === \$this->hasParentNode) {
			$peerClassname::hasParent(\$this, \$con);
		}
		return \$this->hasParentNode;
	}
";
	}

	protected function addHasChildren(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Determines if the node has children / descendants
	 *
	 * @return     bool
	 */
	public function hasChildren()
	{
		return  $peerClassname::hasChildren(\$this);
	}
";
	}

	protected function addHasPrevSibling(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Determines if the node has previous sibling
	 *
	 * @param      PDO Connection to use.
	 * @return     bool
	 */
	public function hasPrevSibling(PDO \$con = null)
	{
		if (null === \$this->hasPrevSibling) {
			$peerClassname::hasPrevSibling(\$this, \$con);
		}
		return \$this->hasPrevSibling;
	}
";
	}

	protected function addHasNextSibling(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Determines if the node has next sibling
	 *
	 * @param      PDO Connection to use.
	 * @return     bool
	 */
	public function hasNextSibling(PDO \$con = null)
	{
		if (null === \$this->hasNextSibling) {
			$peerClassname::hasNextSibling(\$this, \$con);
		}
		return \$this->hasNextSibling;
	}
";
	}

	protected function addRetrieveParent(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Gets ancestor for the given node if it exists
	 *
	 * @param      PDO \$con      Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public function retrieveParent(PDO \$con = null)
	{
		if (null === \$this->hasParentNode) {
			\$this->parentNode = $peerClassname::retrieveParent(\$this, \$con);
			\$this->hasParentNode = $peerClassname::isValid(\$this->parentNode);
		}
		return \$this->parentNode;
	}
";
	}

	protected function addRetrieveFirstChild(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Gets first child if it exists
	 *
	 * @param      PDO \$con      Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public function retrieveFirstChild(PDO \$con = null)
	{
		if (\$this->hasChildren(\$con)) {
			if (is_array(\$this->_children)) {
				return \$this->_children[0];
			}

			return $peerClassname::retrieveFirstChild(\$this, \$con);
		}
		return false;
	}
";
	}

	protected function addRetrieveLastChild(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Gets last child if it exists
	 *
	 * @param      PDO \$con      Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public function retrieveLastChild(PDO \$con = null)
	{
		if (\$this->hasChildren(\$con)) {
			if (is_array(\$this->_children)) {
				\$last = count(\$this->_children) - 1;
				return \$this->_children[\$last];
			}

			return $peerClassname::retrieveLastChild(\$this, \$con);
		}
		return false;
	}
";
	}

	protected function addRetrievePrevSibling(&$script)
	{
		$script .= "
	/**
	 * Gets prev sibling for the given node if it exists
	 *
	 * @param      PDO \$con      Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public function retrievePrevSibling(PDO \$con = null)
	{
		if (\$this->hasPrevSibling(\$con)) {
			return \$this->prevSibling;
		}
		return \$this->hasPrevSibling;
	}
";
	}

	protected function addRetrieveNextSibling(&$script)
	{
		$script .= "
	/**
	 * Gets next sibling for the given node if it exists
	 *
	 * @param      PDO \$con      Connection to use.
	 * @return     mixed 		Propel object if exists else false
	 */
	public function retrieveNextSibling(PDO \$con = null)
	{
		if (\$this->hasNextSibling(\$con)) {
			return \$this->nextSibling;
		}
		return \$this->hasNextSibling;
	}
";
	}

	protected function addInsertAsFirstChildOf(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Inserts as first child of given destination node \$dest
	 *
	 * @param      object \$dest	Propel object for destination node
	 * @param      PDO Connection to use.
	 * @return     object		Inserted propel object for model
	 */
	public function insertAsFirstChildOf(BaseNodeObject \$dest, PDO \$con = null)
	{
		return $peerClassname::insertAsFirstChildOf(\$this, \$dest, \$con);
	}
";
	}

	protected function addInsertAsLastChildOf(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Inserts as last child of given destination node \$dest
	 *
	 * @param      object \$dest	Propel object for destination node
	 * @param      PDO Connection to use.
	 * @return     object		Inserted propel object for model
	 */
	public function insertAsLastChildOf(BaseNodeObject \$dest, PDO \$con = null)
	{
		return $peerClassname::insertAsLastChildOf(\$this, \$dest, \$con);
	}
";
	}

	protected function addInsertAsPrevSiblingOf(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Inserts \$node as previous sibling to given destination node \$dest
	 *
	 * @param      object \$dest	Propel object for destination node
	 * @param      PDO Connection to use.
	 * @return     object		Inserted propel object for model
	 */
	public function insertAsPrevSiblingOf(BaseNodeObject \$dest, PDO \$con = null)
	{
		return $peerClassname::insertAsPrevSiblingOf(\$this, \$dest, \$con);
	}
";
	}

	protected function addInsertAsNextSiblingOf(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$script .= "
	/**
	 * Inserts \$node as next sibling to given destination node \$dest
	 *
	 * @param      object \$dest	Propel object for destination node
	 * @param      PDO Connection to use.
	 * @return     object		Inserted propel object for model
	 */
	public function insertAsNextSiblingOf(BaseNodeObject \$dest = null, PDO \$con = null)
	{
		return $peerClassname::insertAsNextSiblingOf(\$this, \$dest, \$con);
	}
";
	}

	protected function addGetLeft(&$script)
	{
		$table = $this->getTable();

		foreach ($table->getColumns() as $col) {
			if ($col->isNestedSetLeftKey()) {
				$left_col_getter_name = 'get'.$col->getPhpName();
				break;
			}
		}

		$script .= "
	/**
	 * Wraps the getter for the left value
	 *
	 * @return     int
	 */
	public function getLeftValue()
	{
		return \$this->$left_col_getter_name();
	}
";
	}

	protected function addGetRight(&$script)
	{
		$table = $this->getTable();

		foreach ($table->getColumns() as $col) {
			if ($col->isNestedSetRightKey()) {
				$right_col_getter_name = 'get'.$col->getPhpName();
				break;
			}
		}

		$script .= "
	/**
	 * Wraps the getter for the right value
	 *
	 * @return     int
	 */
	public function getRightValue()
	{
		return \$this->$right_col_getter_name();
	}
";
	}

	protected function addGetScopeId(&$script)
	{
		$table = $this->getTable();

		$scope_col_getter_name = null;
		foreach ($table->getColumns() as $col) {
			if ($col->isNestedSetScopeKey()) {
				$scope_col_getter_name = 'get'.$col->getPhpName();
				break;
			}
		}

		$script .= "
	/**
	 * Wraps the getter for the scope value
	 *
	 * @return     int
	 */
	public function getScopeIdValue()
	{";
		if($scope_col_getter_name) {
			$script .= "
		return \$this->$scope_col_getter_name();";
		}
		$script .= "
	}
";
	}

	protected function addSetLeft(&$script)
	{
		$table = $this->getTable();

		foreach ($table->getColumns() as $col) {
			if ($col->isNestedSetLeftKey()) {
				$left_col_setter_name = 'set'.$col->getPhpName();
				break;
			}
		}

		$script .= "
	/**
	 * Set the value left column
	 *
	 * @param      int \$v new value
	 * @return     void
	 */
	public function setLeftValue(\$v)
	{
		\$this->$left_col_setter_name(\$v);
	}
";
	}

	protected function addSetRight(&$script)
	{
		$table = $this->getTable();

		foreach ($table->getColumns() as $col) {
			if ($col->isNestedSetRightKey()) {
				$right_col_setter_name = 'set'.$col->getPhpName();
				break;
			}
		}

		$script .= "
	/**
	 * Set the value of right column
	 *
	 * @param      int \$v new value
	 * @return     void
	 */
	public function setRightValue(\$v)
	{
		\$this->$right_col_setter_name(\$v);
	}
";
	}

	protected function addSetScopeId(&$script)
	{
		$table = $this->getTable();
		
		$scope_col_setter_name = null;
		foreach ($table->getColumns() as $col) {
			if ($col->isNestedSetScopeKey()) {
				$scope_col_setter_name = 'set'.$col->getPhpName();
				break;
			}
		}

		$script .= "
	/**
	 * Set the value of scope column
	 *
	 * @param      int \$v new value
	 * @return     void
	 */
	public function setScopeIdValue(\$v)
	{";
		if($scope_col_setter_name) {
			$script .= "
		\$this->$scope_col_setter_name(\$v);";
		}
		$script .= "
	}
";

	}

} // PHP5NestedSetBuilder
