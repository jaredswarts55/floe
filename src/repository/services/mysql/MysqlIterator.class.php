<?php
/**
 * This file is part of Floe, a graceful web framework.
 * Copyright (C) 2005-2010 Mark Rickerby <http://maetl.net>
 *
 * See the LICENSE file distributed with this software for full copyright, disclaimer
 * of liability, and the specific limitations that govern the use of this software.
 *
 * @package repository
 * @subpackage services.mysql
 */

/**
 * Iterator over a Mysql result set.
 *
 * @package repository
 * @subpackage services.mysql
 */
class MysqlIterator implements Iterator {

	var $_result;
	var $_current;
	var $_count;

	function __construct(&$result) {
		$this->_result = $result;
		$this->_current = 1;
		$this->_count = mysql_num_rows($this->_result);
	}
	
	function current() {
	
	}
	
	function valid() {
	
	}
	
	function key() {
	
	}
	
	function rewind() {
	
	}

	function count() {
		return $this->_count;
	}
	
	function next() {
		if ($this->_current == $this->_count) {
			$this->close();
			return null;
		}
		if (!($row = mysql_fetch_object($this->_result))) {
			$this->_current++;
			return null;
		}
		return $row;
	}
	
	function close() {
		$this->_current = 0;
	}
	
}
