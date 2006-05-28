<?php

/**
 *
 *
 * @version $Revision$
 */
interface ExpressionContainer extends Expression, IteratorAggregate {

	/**
	 * Adds an Expression to this container.
	 * @param Expression $e
	 */
	public function add(Expression $e);
}
