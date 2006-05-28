<?php


require_once 'propel/util/criteria/BaseExpression.php';



class BaseExpressionContainer extends BaseExpression {

	/**
	 * Sets the QueryTable for this expression and all children (unless they've been explicitly set).
	 * @param QueryTable $table
	 */
	public function setQueryTable(QueryTable $table)
	{
		parent::setQueryTable($table);
		foreach($this as $expr) {
			if ($expr->getQueryTable() === null) {
				$expr->setQueryTable($table);
			}
		}
	}

	/**
     * Sets ignore case for this expression and all children (unless they've been explicitly set).
     *
     * @param boolean $b True if case should be ignored.
     * @return A modified Criteria object.
     */
    public function setIgnoreCase($b)
    {
    	$b = (boolean) $b;
        parent::setIgnoreCase($b);
        foreach($this as $expr) {
			if ($expr->getIgnoreCase() === null) {
				$expr->setIgnoreCase($b);
			}
		}
        return $this;
    }
}
