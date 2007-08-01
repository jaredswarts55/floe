<?php
//
// +------------------------------------------------------------------------+
// | core.os.repository.storage.db.mysql.MysqlAdaptor                                                    |
// +------------------------------------------------------------------------+
// | Copyright (c) 2005 Coretxt Design <coretxt@gmail.com>.                 |
// +------------------------------------------------------------------------+
// | This source file is subject to the Artistic License                    |
// | available at http://www.opensource.org/licenses/artistic-license.php   |
// +------------------------------------------------------------------------+
//
// $Id: MysqlAdaptor.class.php 61 2007-07-02 15:00:31Z maetl_ $
//
/**
 * @package repository
 * @subpackage storage
 * @subpackage db
 */
require_once 'MysqlIterator.class.php';

/**
 * <p>Gateway for managing common database operations.</p>
 *
 * @package repository
 * @subpackage storage
 * @subpackage db
 */
 class MysqlAdaptor {

	var $_connection;
	var $_result;
	var $_currentTable;

	function MysqlAdaptor($connection) {
		$this->_connection = $connection;
	}
	
	function affectedRows() {
		return mysql_affected_rows($this->_result);
	}
	
	/**
	 * @deprecated
	 * @todo remove
	 */
	function insertId() {
		return mysql_insert_id();
	}
	
	/**
	 * <p>Returns an object as the result of a select query.</p>
	 * 
	 * @return stdClass
	 */
	function getRecord() {
		$object = $this->getObject();
		if ($object) {
			$record = Inflector::toIdentifier($this->_currentTable);
			$record = Inflector::singularize($record);
			if (!class_exists($record)) {
				$_properties = "";
				$i = 0;
				/*while ($i < mysql_num_fields($this->_result)) {
					$meta = mysql_fetch_field($this->_result, $i);
					if (!$meta) {
					} else {
						$_properties .= " \$this->property('" .$meta->name. "','" .$this->definePropertyType($meta->type). "');";
					}
				}*/
				$_define = "function __define(){" .$_properties. "}";
				eval("class $record extends Record { " .$_define. " }");
			}
			return new $record($object);
		} else {
			return null;
		}
	}
	
	/**
	 * <p>Returns a Record as the result of a select query.</p>
	 * 
	 * @return stdClass
	 */
	function getObject() {
		return mysql_fetch_object($this->_result);
	}
	
	/**
	 * <p>Returns an array of entity objects as the result of a select query.</p>
	 *
	 * @return array<Record>
	 */
	function getRecords() {
		$i=0; $list = array();
		while ($row = mysql_fetch_object($this->_result)) {
			$table = Inflector::toIdentifier($this->_currentTable);
			$objects[$i] = new $table($row); $i++;
		}
		return $objects;
	}

	/**
	 * <p>Returns an array of objects as the result of a select query.</p>
	 *
	 * @return array<Record>
	 */
	function getObjects() {
		$i=0; $list = array();
		while ($row = mysql_fetch_object($this->_result)) {
			$objects[$i] = $row; $i++;
		}
		return $objects;
	}
	
	/**
	 * <p>Returns an iterator for traversing the result of a select query.</p>
	 *
	 * @return Iterator<stdClass>
	 */
	function getIterator() {
		return new MysqlIterator($this->_result);
	}
	
	/**
	 *
	 */
	function selectById($table, $id) {
		$this->_currentTable = $table;
		$sql = 'SELECT * FROM '.$table.' WHERE id="'.$id.'"';
		$this->_result = $this->_connection->execute($sql);
	}
	
	/**
	 *
	 */
	function selectAll($table) {
		$this->_currentTable = $table;
		$sql = 'SELECT * FROM '.$table;
		$this->_result = $this->_connection->execute($sql);
	}

	/**
	 *
	 */	
	function selectByKey($table, $target) {
		$this->_connection->connect();
		$this->_currentTable = $table;
		$sql = 'SELECT * FROM '.$table.' WHERE ';
		$where = '';
		foreach ($target as $key => $value) {
			if($where != "") {
				$where .= "AND ";
			}
			$where .= mysql_real_escape_string($key) .'="'. mysql_real_escape_string($value) .'" ';
		}
		$sql .= $where;
		$this->_result = $this->_connection->execute($sql);
	}
	
	/**
	 *
	 */
	 function selectByAssociation($table, $join_table, $target) {
		 $sql = "SELECT * FROM $table,$join_table ";
		 $sql .= "WHERE $table.".Inflector::singularize($join_table)."_id=$join_table.id ";
		 $sql .= "AND $join_table.".key($target)."='".current($target)."'";
		 $this->_result = $this->_connection->execute($sql);
	 }
	
	/**
	 * <p>Inserts a row into specified table.</p>
	 * 
	 * @deprecated in favor of create
	 * @param $table string name of the table to insert into
	 * @param $columns associative array of column=>value pairs to create
	 */
	function insert($table, $columns) {
		$this->_connection->connect();
		$this->currentTable = $table;
		$keys = array_keys($columns);
		$values = array_values($columns);
		$colnum = count($columns);
		$sql = 'INSERT INTO '.mysql_real_escape_string($table).' (';
		for($i=0;$i<$colnum;$i++) {
			$sql .= $keys[$i];
			$i==($colnum-1) ? $sql .= ')' : $sql .= ',';
		}
		$sql .= ' VALUES (';
		for($i=0;$i<$colnum;$i++) {
			$sql .= '"'.mysql_real_escape_string($values[$i]).'"';
			$i==($colnum-1) ? $sql .= ')' : $sql .= ',';
		}
		$this->_connection->execute($sql);
	}
	
	/**
	 * Create a new record
	 */
	function create($table, $columns) {
		$this->insert($table, $columns);
	}
	
	/**
	 * Updates a row in specified table
	 */
	function update($table, $target, $columns) {
		$this->_connection->connect();
		$colnum = count($columns);
		$i = 1;
		$sql = 'UPDATE '.mysql_real_escape_string($table).' SET ';
		foreach($columns as $field=>$val) {
			$sql .= $field.'="'.$val.'"';
			$i==$colnum ? $sql .= ' ' : $sql .= ',';
			$i++;
		}
		$sql .= 'WHERE '.mysql_real_escape_string(key($target)).'="'.mysql_real_escape_string(current($target)).'"';
		$this->_connection->execute($sql);
	}
	
	/**
	 * Deletes row(s) specified by column=>value pair
	 *
	 */
	function delete($table, $target) {
		if (!is_array($target)) return;
		$sql = 'DELETE FROM '.$table.' WHERE '.key($target).'="'.current($target).'"';
		$this->_connection->execute($sql);
	}

	/** 
	 * Creates a table with specified columns
	 */
	function createTable($table, $rows, $type='MyISAM') {
		$sql = "\nCREATE TABLE `$table` (";
		$sql .= "\nid int(11) NOT NULL auto_increment,";
		foreach($rows as $key=>$val) {
			$sql .= "\n$key ";
			$sql .= $this->defineType($val);
			$sql .= ',';
		}
		$sql .= "\nPRIMARY KEY  (`id`)\n";
		$sql .= ") TYPE=$type AUTO_INCREMENT=1 ;";
		$this->_connection->execute($sql);
	}
	
	/**
	 * Destroys an existing table and all its data
	 */
	 function dropTable($table) {
		 $this->_connection->execute("DROP TABLE $table");
	 }

 	/**
	 * Checks if a table exists
	 */
	 function tableExists($table) {
		 $this->_connection->execute("DROP TABLE IF EXISTS $table");
	 }
	 
	/**
	 * Rename a table column without altering it's structure
	 */
	function changeColumn($table, $oldCol, $newCol, $type=false) {
		if (!$type) {
			$sql = "SHOW FIELDS FROM $table LIKE '$oldCol'";
			$this->_result = $this->_connection->execute($sql);
			$field = $this->getObject();
			$definition = $field->Type;
		} else {
			$definition = $this->defineType($type);
		}
		$sql = "ALTER TABLE $table CHANGE COLUMN $oldCol $newCol ". $definition;
		$this->_connection->execute($sql);
	}
	
	/**
	 * Add a new table column
	 */
	 function addColumn($table, $name, $type) {
		 $sql = "ALTER TABLE $table ADD COLUMN $name " . $this->defineType($type);
		 $this->_connection->execute($sql);
	 }
	 
	 /**
	  * Returns the mapping to a framework type class
	  */
	 function definePropertyType($type) {
	 	return 'string';
	 }
	 
	 /**
	  * <p>Gets native SQL definition for a column type.</p>
	  *
	  * @return string SQL definition
	  */
	 function defineType($type) {
		 switch($type) {
				case 'int':
				case 'integer':
				case 'number':
					return "INT(11)";
				case 'float':
					return "FLOAT(10,10) ZEROFILL";
				case 'text':
					return"TEXT NOT NULL default ''";
				case 'date': 
					return "DATE NOT NULL default '0000-00-00'";
				case 'datetime':
					return "DATETIME NOT NULL default '0000-00-00 00:00:00'";
				case 'raw':
					return "BLOB NOT NULL default ''";
				default:
					return "VARCHAR(255) NOT NULL default ''";
			}
	 }
	
}

?>