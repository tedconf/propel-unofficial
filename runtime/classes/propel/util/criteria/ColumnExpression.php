 <?php

abstract class ColumnExpression extends BaseExpression {

	private $colname;

	protected function __construct($colname)
	{
		$this->colname = $colname;
	}

	/**
     * Gets the DBAdapter based on curent TableMap (and its DatabaseMap).
     * @return DBAdapter
     * @throws PropelException if TableMap was not set.
     */
    protected function getAdapter()
    {
    	$qt = $this->getQueryTable();
		if ($qt === null) {
			throw new PropelException("QueryTable must be set in Expression (setQueryTable(QueryTable)) before you can call getAdapter()");
		}
		return $qt->getTableMap()->getDatabase()->getAdapter();
	}

	/**
     * Gets the resolved column (resolved based on curent QueryTable).
     * @param string $colname
     * @return QueryColumn
     * @throws PropelException if TableMap was not set or column is invalid.
     */
    protected function getQueryColumn()
    {
    	$qt = $this->getQueryTable();
		if ($qt === null) {
			throw new PropelException("The QueryTable must be set in Expression (setTable(TableMap)) before you can evaluate it.");
		}
		return $qt->createQueryColumn($this->colname);
	}

}
