<?php

abstract class ColumnValueExpression extends ColumnExpression implements Expression {

	protected $value;

	public function __construct($colname, $value)
	{
		parent::__construct($colname);
		$this->value = $value;
	}

	abstract protected function getOperator();

	public function buildSql(&$sql, &$values)
	{
		$col = $this->getQueryColumn();

		if ($this->value !== null) {

			// default case, it is a normal col = value expression; value
			// will be replaced w/ '?' and will be inserted later using PDO bindValue()
			if ($this->getIgnoreCase()) {
				$sql .= $this->getIgnoreCaseSql();
		    } else {
	        	$sql .= $col->getQualifiedName() . $this->getOperator() . "?";
	    	}

			// need to track the field in params, because
			// we'll need it to determine the correct setter
			// method later on (e.g. field 'review.DATE' => setDate());
			$values[] = new ColumnValue($col, $this->value);

		} else {
			// value is null, which means it was either not specified or specifically
            // set to null.

			$sql .= $this->getNullValueSql();
		}
	}

	protected function getIgnoreCaseSql()
	{
		$col = $this->getQueryColumn();
		return $col->ignoreCase($col->getQualifiedName()) . $this->getOperator() . $col->ignoreCase("?");
	}

	/**
	 * Builds SQL if the value is NULL
	 */
	protected function getNullValueSql()
	{
		throw new PropelException("Could not build SQL for expression: {$this->colname}" . $this->getOperator() . " NULL");
	}

}
