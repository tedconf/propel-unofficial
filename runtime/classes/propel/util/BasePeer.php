<?php
/*
 *  $Id$
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

include_once 'propel/adapter/DBAdapter.php';
include_once 'propel/map/ColumnMap.php';
include_once 'propel/map/DatabaseMap.php';
include_once 'propel/map/TableMap.php';
include_once 'propel/map/ValidatorMap.php';
include_once 'propel/util/Query.php';
include_once 'propel/util/Transaction.php';
include_once 'propel/util/PropelColumnTypes.php';
include_once 'propel/validator/ValidationFailed.php';

/**
 * This is a utility class for all generated Peer classes in the system.
 *
 * This class is responsible for building and executing the SQL statements and queries
 * needed by the actual Peer implementation classes. 
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Kaspars Jaudzems <kaspars.jaudzems@inbox.lv>
 * @version $Revision$
 * @package propel.util
 */
class BasePeer
{

	/** Array (hash) that contains the cached mapBuilders. */
	private static $mapBuilders = array();

	/** Array (hash) that contains cached validators */
	private static $validatorMap = array();

	/**
	 * phpname type
	 * e.g. 'AuthorId'
	 */
	const TYPE_PHPNAME = 'phpName';

	/**
	 * column (peer) name type
	 * e.g. 'book.AUTHOR_ID'
	 */
	const TYPE_COLNAME = 'colName';

	/**
	 * column fieldname type
	 * e.g. 'author_id'
	 */
	const TYPE_FIELDNAME = 'fieldName';

	/**
	 * num type
	 * simply the numerical array index, e.g. 4
	 */
	const TYPE_NUM = 'num';

	static public function getFieldnames ($classname, $type = self::TYPE_PHPNAME) {

		// TODO we should take care of including the peer class here

		$peerclass = 'Base' . $classname . 'Peer'; // TODO is this always true?
		$callable = array($peerclass, 'getFieldnames');
		$args = array($type);

		return call_user_func_array($callable, $args);
	}

	static public function translateFieldname($classname, $fieldname, $fromType, $toType) {

		// TODO we should take care of including the peer class here

		$peerclass = 'Base' . $classname . 'Peer'; // TODO is this always true?
		$callable = array($peerclass, 'translateFieldname');
		$args = array($fieldname, $fromType, $toType);

		return call_user_func_array($callable, $args);
	}

	/**
	 * Method to perform deletes based on values and keys in a Criteria object.
	 *
	 * @param Criteria $criteria The criteria to use.
	 * @param PDO $con A PDO connection object.
	 * @return int	The number of rows affected by last statement execution.  For most
	 * 				uses there is only one delete statement executed, so this number
	 * 				will correspond to the number of rows affected by the call to this
	 * 				method.  Note that the return value does require that this information
	 * 				is returned (supported) by the PDO driver.
	 * @throws PropelException
	 */
	public static function doDelete(Criteria $criteria, PDO $con)
	{
		$dbname = $criteria->getDbName();
		
		$db = Propel::getAdapter($dbname);
		$dbMap = Propel::getDatabaseMap($dbname);
		
		$tableName = $criteria->getQueryTable()->getName();

		$bindParams = array();
		$whereClause = $criteria->buildSql($bindParams);
				
		if (empty($whereClause)) {
			throw new PropelException("Cowardly refusing to perform DELETE on table $tableName with empty WHERE clause.");
		}

		// Execute the statement.
		try {
			$sql = "DELETE FROM " . $tableName . " WHERE " . $whereClause;
			Propel::log($sql, Propel::LOG_DEBUG);
			$stmt = $con->prepare($sql);
			self::populateStmtValues($stmt, $bindParams, $db);
			$stmt->execute();
			$affectedRows = $stmt->rowCount();
		} catch (Exception $e) {
			Propel::log($e->getMessage(), Propel::LOG_ERR);
			throw new PropelException("Unable to execute DELETE statement.",$e);
		}

		return $affectedRows;
	}

	/**
	 * Method to deletes all contents of specified table.
	 *
	 * This method is invoked from generated Peer classes like this:
	 * <code>
	 * public static function doDeleteAll($con = null)
	 * {
	 *   if ($con === null) $con = Propel::getConnection(self::DATABASE_NAME);
	 *   BasePeer::doDeleteAll(self::TABLE_NAME, $con);
	 * }
	 * </code>
	 *
	 * @param string $tableName The name of the table to empty.
	 * @param PDO $con A PDO connection object.
	 * @return int	The number of rows affected by the statement.  Note
	 * 				that the return value does require that this information
	 * 				is returned (supported) by the Creole db driver.
	 * @throws PropelException - wrapping SQLException caught from statement execution.
	 */
	public static function doDeleteAll($tableName, PDO $con)
	{
		try {
			$sql = "DELETE FROM " . $tableName;
			Propel::log($sql, Propel::LOG_DEBUG);
			$stmt = $con->prepare($sql);
			return $stmt->execute();
		} catch (Exception $e) {
			Propel::log($e->getMessage(), Propel::LOG_ERR);
			throw new PropelException("Unable to perform DELETE ALL operation.", $e);
		}
	}

	/**
	 * Method to perform inserts based on values and keys in a
	 * Criteria.
	 * <p>
	 * If the primary key is auto incremented the data in Criteria
	 * will be inserted and the auto increment value will be returned.
	 * <p>
	 * If the primary key is included in Criteria then that value will
	 * be used to insert the row.
	 * <p>
	 * If no primary key is included in Criteria then we will try to
	 * figure out the primary key from the database map and insert the
	 * row with the next available id using util.db.IDBroker.
	 * <p>
	 * If no primary key is defined for the table the values will be
	 * inserted as specified in Criteria and null will be returned.
	 *
	 * @param InsertValues $values  
	 * @param PDO $con A PDO connection.
	 * @return mixed The primary key for the new row if (and only if!) the primary key 
	 * 					is auto-generated.  Otherwise will return <code>null</code>.
	 * @throws PropelException
	 */
	public static function doInsert(ColumnValueCollection $values, PDO $con)
	{
		$tableMap = $values->getTableMap();
		$dbMap = $tableMap->getDatabase();
		$db = $dbMap->getAdapter();
		
		if ($values->size() === 0) {
			throw new PropelException("Database insert attempted without anything specified to insert");
		}
		
		$useIdGen = $tableMap->isUseIdGenerator();
		
		// the primary key
		$id = null;
		
		// the primary key column
		$pk = null;
		
		if ($useIdGen) { // only call this method if we're using auto-incremnet pkey
			$pks = $tableMap->getPrimaryKey();
			$pk = $pks[0]; // we assume there is only one pkey for an autoincrement table. (violate that at your own risk!)
		}

		// pk will be null if there is no primary key defined for the table
		// we're inserting into.
		if ($pk !== null && $useIdGen && !$values->containsKey($pk->getName()) && $db->isGetIdBeforeInsert()) {
			try {
				$id = $db->getId($con, $tableMap->getPrimaryKeyMethodInfo());
			} catch (Exception $e) {
				throw new PropelException("Unable to get sequence id.", $e);
			}
			$values->add($pk->getName(), $id);
		}

		try {

			$columns = $values->keys(); // we need table.column cols when populating values

			$sql = "INSERT INTO " . $tableMap->getName()
				. " (" . implode(",", $columns) . ")"
				. " VALUES (" . substr(str_repeat("?,", count($columns)), 0, -1) . ")";

			Propel::log($sql, Propel::LOG_DEBUG);

			$stmt = $con->prepare($sql);
			self::populateStmtValues($stmt, $values, $db);
			$stmt->execute();

		} catch (Exception $e) {
			Propel::log($e->getMessage(), Propel::LOG_ERR);
			throw new PropelException("Unable to execute INSERT statement.", $e);
		}

		// If the primary key column is auto-incremented, get the id now.
		if ($pk !== null && $useIdGen && $db->isGetIdAfterInsert()) {
			try {
				$id = $db->getId($con, $tableMap->getPrimaryKeyMethodInfo());
			} catch (Exception $e) {
				throw new PropelException("Unable to get autoincrement id.", $e);
			}
		}

		return $id;
	}

	/**
	 * Method used to update rows in the DB.  Rows are selected based
	 * on selectCriteria and updated using values in updateValues.
	 * <p>
	 * Use this method for performing an update of the kind:
	 * <p>
	 * WHERE some_column = some value AND could_have_another_column =
	 * another value AND so on.
	 *
	 * @param Criteria $selectCriteria A Criteria object containing values used in where clause.
	 * @param ColumnValueCollection $updateValues A collection of ColumnValue objects containing the values to be used in set clause.
	 * @param PDO $con The PDO connection object to use.
	 * @return int	The number of rows affected by last update statement.  For most
	 * 				uses there is only one update statement executed, so this number
	 * 				will correspond to the number of rows affected by the call to this
	 * 				method.  Note that the return value does require that this information
	 * 				is returned (supported) by the Creole db driver.
	 * @throws PropelException
	 */
	public static function doUpdate(Criteria $selectCriteria, ColumnValueCollection $updateValues, PDO $con)
	{
		$tableMap = $updateValues->getTableMap();
		$dbMap = $tableMap->getDatabase();
		$db = $dbMap->getAdapter();
		
		$affectedRows = 0; // initialize this in case the next loop has no iterations.

		$bindParams = array();
		$whereClause = $selectCriteria->buildSql($bindParams);
		
		if (empty($whereClause)) {
			throw new PropelException("Cowardly refusing to perform UPDATE on table $tableName with empty WHERE clause.");
		}
		
		$stmt = null;
		try {

			$sql = "UPDATE " . $tableMap->getName() . " SET ";
			
			
			$updateValuseArray = array(); // need this Collection turned into an array so we can merge it with $bindParams later
			
			foreach($updateValues as $colname => $cv) {
				$sql .= $colname . " = ?,";
				$updateValuesArray[] = $cv; 
			}

			$sql = substr($sql, 0, -1) . " WHERE " .  $whereClause;

			Propel::log($sql, Propel::LOG_DEBUG);

			$stmt = $con->prepare($sql);

			// Replace '?' with the actual values
			self::populateStmtValues($stmt, array_merge($updateValuesArray, $bindParams), $db);

			$stmt->execute();

			$affectedRows = $stmt->rowCount();

			$stmt = null; // close

		} catch (Exception $e) {
			if ($stmt) $stmt = null; // close
			Propel::log($e->getMessage(), Propel::LOG_ERR);
			throw new PropelException("Unable to execute UPDATE statement.", $e);
		}

		return $affectedRows;
	}

	/**
	 * Executes query built by createSelectSql() and returns PDOStatement.
	 *
	 * @param Criteria $criteria A Criteria.
	 * @param PDO $con A PDO connection to use.
	 * @return PDOStatement The result statement.
	 * @throws PropelException
	 * @see createSelectSql()
	 */
	public static function doSelect(Query $query, PDO $con)
	{
		$dbMap = $query->getQueryTable()->getTableMap()->getDatabase();
		$db = $dbMap->getAdapter();

		$stmt = null;

		try {

			// Transaction support exists for (only?) Postgres, which must
			// have SELECT statements that include bytea columns wrapped w/
			// transactions.
			if ($query->isUseTransaction()) Transaction::begin($con);

			$params = array();
			$sql = self::createSelectSql($query, $params);

 			$stmt = $con->prepare($sql);

 			// FIXME - add SQL-modification for LIMIT/OFFSET into DBAdapters & createSelectSql method.
			// $stmt->setLimit($criteria->getLimit());
			// $stmt->setOffset($criteria->getOffset());

			self::populateStmtValues($stmt, $params, $db);

			$stmt->execute();
			
			if ($query->isUseTransaction()) Transaction::commit($con);

		} catch (Exception $e) {
			if ($stmt) $stmt = null; // close
			if ($query->isUseTransaction()) Transaction::rollback($con);
			Propel::log($e->getMessage(), Propel::LOG_ERR);
			throw new PropelException($e);
		}

		return $stmt;
	}

	/**
	 * Populates values in a prepared statement.
	 *
	 * @param PreparedStatement $stmt
	 * @param mixed $bindValues ColumnValue[] or ColumnValueCollection
	 * @param DatabaseMap $dbMap
	 * @return int The number of params replaced.
	 */
	private static function populateStmtValues($stmt, $bindValues, DBAdapter $db)
	{
		$i = 1;
		
		foreach($bindValues as $columnValue) {
		
			$value = $columnValue->getValue();
			
			if ($value === null) {
			
				$stmt->bindValue($i++, null, PDO::PARAM_NULL);

			} else {
				
				$cMap = $columnValue->getColumnMap();
				$type = $cMap->getType();
				$pdoType = $cMap->getPdoType();

				if (is_numeric($value) && $cMap->isEpochTemporalType()) { // it's a timestamp that needs to be formatted
					if ($type == PropelColumnTypes::TIMESTAMP) {
						$value = date($db->getTimestampFormatter(), $value);
					} else if ($type == PropelColumnTypes::DATE) {
						$value = date($db->getDateFormatter(), $value);
					} else if ($type == PropelColumnTypes::TIME) {
						$value = date($db->getTimeFormatter(), $value);
					}
				}

				Propel::log("Binding " . var_export($value, true) . " at position $i w/ Propel type $type and PDO type $pdoType", Propel::LOG_DEBUG);

				$stmt->bindValue($i++, $value, $pdoType);
			}
		} // foreach
	}

	/**
	 * Applies any validators that were defined in the schema to the specified columns.
	 *
	 * @param string $dbName The name of the database
	 * @param string $tableName The name of the table
	 * @param array $columns Array of column names as key and column values as value.
	 */
	public static function doValidate($dbName, $tableName, $columns)
	{
		$dbMap = Propel::getDatabaseMap($dbName);
		$tableMap = $dbMap->getTable($tableName);
		$failureMap = array(); // map of ValidationFailed objects
		foreach($columns as $colName => $colValue) {
			if ($tableMap->containsColumn($colName)) {
				$col = $tableMap->getColumn($colName);
				foreach($col->getValidators() as $validatorMap) {
					$validator = BasePeer::getValidator($validatorMap->getClass());
					if($validator && ($col->isNotNull() || $colValue !== null) && $validator->isValid($validatorMap, $colValue) === false) {
						if (!isset($failureMap[$colName])) { // for now we do one ValidationFailed per column, not per rule
							$failureMap[$colName] = new ValidationFailed($colName, $validatorMap->getMessage(), $validator);
						}
					}
				}
			}
		}
		return (!empty($failureMap) ? $failureMap : true);
	}

	/**
	 * Method to create an SQL query based on values in a Criteria.
	 *
	 * This method creates only prepared statement SQL (using ? where values
	 * will go).  The second parameter ($params) stores the values that need
	 * to be set before the statement is executed.  The reason we do it this way
	 * is to let the PDO layer handle all escaping & value formatting.
	 *
	 * @param Criteria $criteria Criteria for the SELECT query.
	 * @param array &$params Parameters that are to be replaced in prepared statement.
	 * @return string
	 * @throws PropelException Trouble creating the query string.
	 */
	public static function createSelectSql(Query $query, &$bindParams) {
		
		$criteria = $query->getCriteria();
		$dbname = $criteria->getDbName();
		
		// we don't need to use DATABASE_NAME constants anymore, but clearly
		// it involves a little less dereferencing ....
		// $dbMap = $criteria->getQueryTable()->getTableMap()->getDatabase();
		
		// FIXME - these methods should be re-thought, since there's a more efficient
		// way to get the map directly from Criteria
		$db = Propel::getAdapter($dbname);		
		$dbMap = Propel::getDatabaseMap($dbname);


		// redundant definition $selectModifiers = array();
		$selectClause = array();
		$fromClause = array();
		$joinClause = array();
		$joinTables = array();
		$whereClause = array();
		$orderByClause = array();
		// redundant definition $groupByClause = array();

		$orderBy = $query->getOrderByColumns();
		$groupBy = $query->getGroupByColumns();
		
		// FIXME ... we should try to handle this on a Criteria-by-Criteria basis 
		$ignoreCase = $criteria->getIgnoreCase();
		
		$selectColumns = $query->getSelectColumns();
		
		foreach($selectColumns as $selCol) {
			$selectClause[] = $selCol->getQualifiedSql();
		}
		
		$selectModifiers = $query->getSelectModifiers();

		// Add the primary table to FROM clause
		
		$fromClause[] = $criteria->getQueryTable()->getFromClauseSql();		
		// FIXME - we need to also add any tables that aren't represented by JOINS
		// For that, we want a $query->getUnjoinedTables() method.
				
		
		// Add the criteria to WHERE clause, adding any params to passed-in array
		$whereClause[] = $criteria->buildSql($bindParams);
		
		// Loop through the joins,
		// joins with a null join type will be added to the FROM clause and the condition added to the WHERE clause.
		// joins of a specified type: the LEFT side will be added to the fromClause and the RIGHT to the joinClause
		// New Code.
		
		foreach ($query->getJoins() as $join) { // we'll only loop if there's actually something here
			
			// FIXME - most of this stuff could be moved into the Join class.  There's no 
			// reason that I can see why it needs to be in BasePeer ...
			
			// The join might have been established using an alias name
			$leftCol = $join->getLeftColumn();
			$rightCol = $join->getRightColumn();

			$leftTable = $join->getLeftTable();
			$rightTable = $join->getRightTable();

			// build the condition
			// TODO - consider allowing more complex conditions here.  We get into some trouble when we actully
			// want to use an Expression interface, however, because Expressions are inherently single-table. 
			if ($ignoreCase) {
				$condition = $leftCol->ignoreCase($leftCol->getQualifiedSql()) . '=' . $rightCol->ignoreCase($rightCol->getQualifiedSql());
			} else {
				$condition = $leftCol->getQualifiedSql() . '=' . $rightCol->getQualifiedSql();
			}

			// add 'em to the queues..
			if ( $join->getJoinType() !== Join::IMPLICIT ) {
				$joinTables[] = $rightTable->getFromClauseSql();
				$joinClause[] = $join->getJoinType() . ' ' . $rightTable->getFromClauseSql() . " ON (".$condition.")";
			} else {
				$fromClause[] = $leftTable->getFromClauseSql();
				$fromClause[] = $rightTable->getFromClauseSql();
				$whereClause[] = $condition;
			}
		}

		// Unique from clause elements
		$fromClause = array_unique($fromClause);

		// tables should not exist in both the from and join clauses
		if ($joinTables && $fromClause) {
			foreach ($fromClause as $fi => $ftableAndAlias) {
				if (in_array($ftableAndAlias, $joinTables)) {
					unset($fromClause[$fi]);
				}
			}
		}

		// Add the GROUP BY columns
		$groupByClause = $groupBy;

		$having = $query->getHaving();
		$havingSql = null;
		if ($having !== null) {
			$havingSql = $having->buildSql($bindParams);
		}
		
		if (!empty($orderBy)) {

			foreach($orderBy as $orderByColumn) {
				$direction = $orderByColumn->getDirection();
				if ($ignoreCase && ($orderByColumn instanceof ActualOrderByColumn) && $orderByColumn->getColumnMap()->isText()) {
					$orderByClause[] = $db->ignoreCaseInOrderBy($orderByColumn->getQualifiedSql()) . ' ' . $direction;
				} else {
					$orderByClause[] = $orderByColumn->getQualifiedSql() . ' ' . $direction;
				}
			}
		}

		// Build the SQL from the arrays we compiled
		$sql =  "SELECT "
				.($selectModifiers ? implode(" ", $selectModifiers) . " " : "")
				.implode(", ", $selectClause)
				." FROM ".implode(", ", $fromClause)
								.($joinClause ? ' ' . implode(' ', $joinClause) : '')
				.($whereClause ? " WHERE ".implode(" AND ", $whereClause) : "")
				.($groupByClause ? " GROUP BY ".implode(",", $groupByClause) : "")
				.($havingSql ? " HAVING ".$havingSql : "")
				.($orderByClause ? " ORDER BY ".implode(",", $orderByClause) : "");
		
		// APPLY OFFSET & LIMIT to the query.
		if ($query->getLimit() || $query->getOffset()) {
			$db->applyLimit($sql, $query->getOffset(), $query->getLimit());
		}
		
		Propel::log($sql . ' [LIMIT: ' . $query->getLimit() . ', OFFSET: ' . $query->getOffset() . ']', Propel::LOG_DEBUG);

		return $sql;
	}

	/**
	 * This function searches for the given validator $name under propel/validator/$name.php,
	 * imports and caches it.
	 *
	 * @param string $classname The dot-path name of class (e.g. myapp.propel.MyValidator)
	 * @return Validator object or null if not able to instantiate validator class (and error will be logged in this case)
	 */
	public static function getValidator($classname)
	{
		try {
			$v = isset(self::$validatorMap[$classname]) ? self::$validatorMap[$classname] : null;
			if ($v === null) {
				$cls = Propel::import($classname);
				$v = new $cls();
				self::$validatorMap[$classname] = $v;
			}
			return $v;
		} catch (Exception $e) {
			Propel::log("BasePeer::getValidator(): failed trying to instantiate " . $classname . ": ".$e->getMessage(), Propel::LOG_ERR);
		}
	}

}
