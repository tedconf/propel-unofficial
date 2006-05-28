<?php


abstract class BaseExpression {

	private $queryTable;
	private $tableAlias;
	private $ignoreCase;

	/**
	 * Sets the QueryTable for this expression.
	 * @param $table QueryTable
	 */
	public function setQueryTable(QueryTable $table)
	{
		$this->queryTable = $table;
	}

	/**
	 * Gets the QueryTable for this expression.
	 * @return QueryTable
	 */
	public function getQueryTable()
	{
		return $this->queryTable;
	}

	/**
     * Sets ignore case.
     *
     * @param boolean $b True if case should be ignored.
     * @return A modified Criteria object.
     */
    public function setIgnoreCase($b)
    {
        $this->ignoreCase = (boolean) $b;
        return $this;
    }

    /**
     * Get the ignore case value.
     *
     * @return boolean True if case is ignored, false otherwise, or NULL if not set.
     */
    public function getIgnoreCase()
    {
        return $this->ignoreCase;
    }

}