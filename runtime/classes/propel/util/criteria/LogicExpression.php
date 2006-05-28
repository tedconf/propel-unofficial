<?php

/**
 * Base class for AND and OR logical expressions.
 */
abstract class LogicExpression extends BaseExpressionContainer implements ExpressionContainer {

	private $expressions = array();

	/**
	 * Create a new AndExpr with variable number of arguments.
	 */
	public function __construct()
	{
		$args = func_get_args();
		foreach($args as $arg) {
			$this->add($arg);
		}
	}


	public function buildSql(&$sql, &$values)
	{
        // each expression gets nested in ()
        $sql .= '(';
        $and = 0;
		foreach($this->expressions as $expr) {
			if ($and++) { $sql .= ' ' . $this->getOperator() . ' '; }
			$expr->buildSql($sql, $values);
		}
		$sql .= ')';
	}

	public function add(Expression $expr)
	{
		if ($expr->getIgnoreCase() === null) {
			$expr->setIgnoreCase($this->getIgnoreCase());
		}
		$this->expressions[] = $expr;
	}

	/**
	 * Return the Iterator (IteratorAggregate itnerface).
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->expressions);
	}

	abstract public function getOperator();

}
