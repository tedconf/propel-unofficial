<?php

abstract class ColumnValueExpression extends ColumnExpression implements Expression {

	protected $value;
	
	/**
	 * Construct a new expression with column name and value.
	 * @param string $colname
	 * @param mixed $value 	Value can be a simple scalar (in which case it is bound to the expression 
	 * 						later using PDOStatement->bindValue()) or it can be a SqlExpr object, 
	 * 						in which case the result of SqlExpr->getSql() is added as the value in the 
	 * 						buildSql() method.
	 */
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
			
			// are we dealing with a traditional value or a SqlExpr ?
			if ($this->value instanceof SqlExpr) {
				// this is a SqlExpr, so the value is going to be the result of SqlExpr->getSql()
				
				// We're not going to honor the ignore sql setting for custom SQL							
				$sql .= $col->getQualifiedSql() . ' ' . $this->getOperator() . ' ' . $this->value->getSql();
				
			} else {
			
				// default case, it is a normal col = value expression; value
				// will be replaced w/ '?' and will be inserted later using PDO bindValue()
				if ($this->getIgnoreCase()) {
					$sql .= $this->getIgnoreCaseSql();
			    } else {
		        	$sql .= $col->getQualifiedSql() . ' ' . $this->getOperator() . ' ?';
		    	}
	
				// need to track the field in params, because
				// we'll need it to determine the correct setter
				// method later on (e.g. field 'review.DATE' => setDate());
				$values[] = new ColumnValue($col->getColumnMap(), $this->value);
			}
						
		} else {
			// value is null, which means it was either not specified or specifically
            // set to null.

			$sql .= $this->getNullValueSql();
		}
	}
	
	/**
	 * Gets the version of the SQL expression to use for a case-insensitive query.
	 * 
	 * In some cases this will be something like UPPER(col) = UPPER(?), but in others
	 * it may actually involve a slightly different expression -- e.g. case-insensitive
	 * version of "col LIKE ?" for PostgreSQL would be "col ILIKE ?".
	 */  
	protected function getIgnoreCaseSql()
	{
		$col = $this->getQueryColumn();
		return $col->ignoreCase($col->getQualifiedSql()) . ' ' . $this->getOperator() . ' ' . $col->ignoreCase('?');
	}

	/**
	 * Builds SQL if the value is NULL
	 */
	protected function getNullValueSql()
	{
		throw new PropelException("Could not build SQL for expression: " . $this->getQueryColumn()->getQualifiedSql() . " ". $this->getOperator() . " NULL");
	}

}
