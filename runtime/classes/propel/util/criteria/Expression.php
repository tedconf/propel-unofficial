<?php


/**
 *
 *
 * @version $Revision$
 */
interface Expression {

	/**
	 *
	 */
	public function buildSql(&$sql, &$values);

	/**
	 *
	 */
	public function setIgnoreCase($bit);

	/**
	 *
	 * @return boolean
	 */
	public function getIgnoreCase();

	/**
	 *
	 */
	public function setQueryTable(QueryTable $table);

	/**
	 *
	 * @return QueryTable
	 */
	public function getQueryTable();

}