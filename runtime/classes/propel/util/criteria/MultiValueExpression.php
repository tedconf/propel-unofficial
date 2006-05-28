<?php

/**
 * Base class for IN and NOT IN expressions.
 */
abstract class MultiValueExpression extends ColumnExpression implements Expression {

	protected $colname;
	protected $values;

	public function __construct($colname, $values)
	{
		parent::__construct($colname);
		$this->values = $values;
	}

	abstract protected function getOperator();

	public function buildSql(&$sql, &$values)
	{
		$col = $this->getColumn();

		if ($this->values !== null) {

			if (empty($this->values)) {
			    // a SQL error will result if we have COLUMN IN (), so replace it with an expression
			    // that will always evaluate to FALSE for Criteria::IN and TRUE for Criteria::NOT_IN
				$sql .= $this->getEmptyValuesSql();
			} else {

				$sql .= $this->getColname() . $this->getOperator();

				foreach($this->values as $value) {
                    $values[] = new ColumnValue($col, $value);
                }

                $inString = '(' . substr(str_repeat("?,", count($this->values)), 0, -1) . ')';
                $sql .= $inString;
			}

		} else {

			// value is null, which means it was either not specified or specifically
            // set to null.

			$sql .= $this->getNullValueSql();
		}

	}

	/**
     * An empty arry will raise a SQL error, so this method returns a SQL expression to use in its place.
     * @return string
     */
	abstract protected function getEmptyValuesSql();

	/**
	 * Builds SQL if the value is NULL
	 */
	protected function getNullValueSql()
	{
		throw new PropelException("Could not build SQL for expression: ".$this->getColname()." ".$this->getOperator()." NULL");
	}

}