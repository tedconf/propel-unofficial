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

require_once 'propel/util/ExpressionClasses.php';
include_once 'propel/util/ColumnValueClasses.php';
include_once 'propel/util/QueryModelClasses.php';

/**
 * This is a utility class for holding criteria information for a query.
 *
 * BasePeer constructs SQL statements based on the values in this class.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 *
 * @version $Revision: 372 $
 * @package propel.util
 */
class Criteria extends BaseExpressionContainer implements ExpressionContainer {

    /**
     * @var ContainerExpression (Defaults to AndExpr if none specified).
     */
    protected $container;

	/**
	 * @var Join[]
	 */
	protected $joins = array();

	/**
	 * @var Expression
	 */
	protected $having;

    /**
     * Construct a new Criteria instance for a specific table.
     */
    public function __construct(QueryTable $table)
    {
		$this->setQueryTable($table);
		$this->container = new AndExpr();
	}

    /**
     * Adds an Expression to this Criteria object.
     */
    public function add(Expression $expr)
    {
    	if ($expr->getIgnoreCase() === null) {
			$expr->setIgnoreCase($this->getIgnoreCase());
		}
		if ($expr->getQueryTable() === null) {
			$expr->setQueryTable($this->getQueryTable());
		}
		$this->container->add($expr);
		return $this;
	}

	/**
	 * This builds the SQL for all expressions that have been added to this Criteria.
	 * @return string The SQL from expressions in this Criteria or NULL if no expressions have been added.
	 */ 
	public function buildSql(&$params)
	{
		return $this->container->buildSql($params);
	}

	/**
	 * Return the Iterator (IteratorAggregate itnerface).
	 */
	public function getIterator()
	{
		if ($this->container) {
			return $this->container->getIterator();
		} else {
			return new ArrayIterator();
		}
	}
	
	/**
	 * This provides the database key based used by the table in this criteria.
	 * 
	 * Since all Criteria must use the same database, we're not going to worry about
	 * issues related to nested Criteria potentially having different database names.  If 
	 * they do, the query will certainly blow up soon enough :)
	 * 
	 * This is used by BasePeer to load up a DBAdapter and DatabaseMap objects.  We may
	 * want to have those objects loaded directly from the Criteria, but at this point,
	 * this is the not-very-efficient, but simpler solution.
	 * 
	 * @return strin
	 */
	public function getDbName()
	{
		return $this->getQueryTable()->getDbName();
	}
}



